<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIDataChatbot
{
    public function __construct(private readonly ProjectDataTools $dataTools)
    {
    }

    public function ask(User $user, string $message, Collection $history): array
    {
        $apiKey = config('services.openai.key');

        // The project works without a paid OpenAI key.
        // When a key is added later, it automatically switches to OpenAI mode.
        if (! $apiKey) {
            return $this->askLocally($user, $message);
        }

        $input = $history
            ->filter(fn ($item) => in_array($item->role, ['user', 'assistant'], true))
            ->map(fn ($item) => [
                'role' => $item->role,
                'content' => $item->content,
            ])
            ->values()
            ->all();

        $input[] = ['role' => 'user', 'content' => $message];
        $tools = $this->dataTools->definitions($user);
        $toolsUsed = [];
        $model = config('services.openai.model', 'gpt-5.6-luna');

        for ($round = 0; $round < 4; $round++) {
            $payload = [
                'model' => $model,
                'instructions' => $this->instructions($user),
                'input' => $input,
                'tools' => $tools,
                'store' => false,
                'safety_identifier' => hash('sha256', 'laravel-user-'.$user->id),
            ];

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(90)
                ->retry(2, 500)
                ->post('https://api.openai.com/v1/responses', $payload);

            if ($response->failed()) {
                throw new RuntimeException(
                    $response->json('error.message') ?: 'OpenAI request failed with HTTP '.$response->status().'.'
                );
            }

            $body = $response->json();
            $output = $body['output'] ?? [];
            $input = array_merge($input, $output);
            $functionCalls = array_values(array_filter(
                $output,
                fn (array $item) => ($item['type'] ?? null) === 'function_call'
            ));

            if ($functionCalls === []) {
                $answer = $this->extractOutputText($output);

                if ($answer === '') {
                    throw new RuntimeException('OpenAI returned no text answer.');
                }

                return [
                    'answer' => $answer,
                    'model' => $body['model'] ?? $model,
                    'tools_used' => array_values(array_unique($toolsUsed)),
                ];
            }

            foreach ($functionCalls as $call) {
                $name = (string) ($call['name'] ?? '');
                $arguments = json_decode((string) ($call['arguments'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
                $result = $this->dataTools->execute($name, $arguments, $user);
                $toolsUsed[] = $name;

                $input[] = [
                    'type' => 'function_call_output',
                    'call_id' => $call['call_id'],
                    'output' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                ];
            }
        }

        throw new RuntimeException('The chatbot exceeded the maximum number of data-tool rounds.');
    }

    private function askLocally(User $user, string $message): array
    {
        $text = mb_strtolower(trim($message));
        $arabic = preg_match('/\p{Arabic}/u', $message) === 1;
        $tool = 'local_database_chatbot';

        if ($this->containsAny($text, ['hello', 'hi', 'hey', 'مرحبا', 'اهلا', 'أهلا', 'السلام عليكم'])) {
            return $this->localResult(
                $arabic
                    ? 'أهلًا! اسأليني عن عدد المنتجات، الأسعار، المخزون، أغلى أو أرخص منتج، أو بيانات المستخدمين لو حسابك Admin.'
                    : 'Hello! Ask me about product counts, prices, stock, the most or least expensive product, or user statistics if you are an admin.',
                $tool
            );
        }

        if ($this->containsAny($text, ['what is this', 'what are you', 'ما هذا', 'ايه ده', 'إيه ده', 'انت مين', 'أنت مين'])) {
            return $this->localResult(
                $arabic
                    ? 'أنا شات بوت داخل مشروع Laravel. أقرأ بيانات المنتجات من قاعدة البيانات وأجيب عن العدد والأسعار والمخزون والبحث عن المنتجات.'
                    : 'I am the data chatbot inside this Laravel project. I read product data from the database and answer questions about counts, prices, stock, and product searches.',
                $tool
            );
        }

        if ($this->containsAny($text, ['help', 'مساعدة', 'اسأل ايه', 'أسأل إيه', 'ممكن اسأل'])) {
            return $this->localResult(
                $arabic
                    ? "جربي أسئلة مثل:\n- كام منتج موجود؟\n- اعرض كل المنتجات\n- إيه أغلى منتج؟\n- كام منتج خلص من المخزون؟\n- سعر المنتج رقم 1 كام؟"
                    : "Try questions such as:\n- How many products are there?\n- Show all products\n- What is the most expensive product?\n- How many products are out of stock?\n- What is the price of product 1?",
                $tool
            );
        }

        if (preg_match('/(?:product|منتج|id)\s*#?\s*(\d+)/iu', $message, $matches)) {
            $result = $this->dataTools->execute('get_product_by_id', ['id' => (int) $matches[1]], $user);

            if (! ($result['found'] ?? false)) {
                return $this->localResult($arabic ? 'المنتج غير موجود.' : 'Product not found.', 'get_product_by_id');
            }

            $product = $result['product'];
            $answer = $arabic
                ? "المنتج رقم {$product['id']}: {$product['name']}، السعر {$product['price']}، والكمية {$product['quantity']}."
                : "Product #{$product['id']}: {$product['name']}, price {$product['price']}, quantity {$product['quantity']}.";

            return $this->localResult($answer, 'get_product_by_id');
        }

        if ($this->containsAny($text, ['user', 'users', 'admin', 'مستخدم', 'مستخدمين', 'ادمن', 'أدمن'])) {
            if (! $user->isAdmin()) {
                return $this->localResult(
                    $arabic ? 'بيانات المستخدمين متاحة لحساب الـAdmin فقط.' : 'User statistics are available to admin accounts only.',
                    $tool
                );
            }

            $stats = $this->dataTools->execute('get_user_statistics', [], $user);
            $answer = $arabic
                ? "عدد المستخدمين {$stats['users_count']}، منهم {$stats['admins_count']} Admin و{$stats['normal_users_count']} مستخدم عادي."
                : "There are {$stats['users_count']} users: {$stats['admins_count']} admins and {$stats['normal_users_count']} normal users.";

            return $this->localResult($answer, 'get_user_statistics');
        }

        if ($this->containsAny($text, ['out of stock', 'no stock', 'نفد', 'خلص', 'غير متوفر'])) {
            $stats = $this->dataTools->execute('get_product_statistics', [], $user);
            $answer = $arabic
                ? "عدد المنتجات غير المتوفرة في المخزون: {$stats['out_of_stock_count']}."
                : "Out-of-stock products: {$stats['out_of_stock_count']}.";

            return $this->localResult($answer, 'get_product_statistics');
        }

        if ($this->containsAny($text, ['low stock', 'مخزون قليل', 'كمية قليلة'])) {
            $stats = $this->dataTools->execute('get_product_statistics', [], $user);
            $answer = $arabic
                ? "عدد المنتجات ذات المخزون القليل من 1 إلى 5 قطع: {$stats['low_stock_count']}."
                : "Products with low stock (1 to 5 units): {$stats['low_stock_count']}.";

            return $this->localResult($answer, 'get_product_statistics');
        }

        if ($this->containsAny($text, ['most expensive', 'highest price', 'اغلى', 'أغلى', 'اعلى سعر', 'أعلى سعر'])) {
            return $this->extremeProduct($user, $arabic, 'desc');
        }

        if ($this->containsAny($text, ['cheapest', 'least expensive', 'lowest price', 'ارخص', 'أرخص', 'اقل سعر', 'أقل سعر'])) {
            return $this->extremeProduct($user, $arabic, 'asc');
        }

        if ($this->containsAny($text, ['statistics', 'stats', 'average price', 'inventory value', 'احصائيات', 'إحصائيات', 'متوسط السعر', 'قيمة المخزون'])) {
            $stats = $this->dataTools->execute('get_product_statistics', [], $user);
            $answer = $arabic
                ? "عدد المنتجات {$stats['products_count']}، إجمالي القطع {$stats['total_units']}، متوسط السعر {$stats['average_price']}، وقيمة المخزون {$stats['inventory_value']}."
                : "Products: {$stats['products_count']}; total units: {$stats['total_units']}; average price: {$stats['average_price']}; inventory value: {$stats['inventory_value']}.";

            return $this->localResult($answer, 'get_product_statistics');
        }

        if ($this->containsAny($text, ['how many products', 'product count', 'number of products', 'كام منتج', 'عدد المنتجات'])) {
            $overview = $this->dataTools->execute('get_database_overview', [], $user);
            $answer = $arabic
                ? "عدد المنتجات الموجودة: {$overview['products_count']}."
                : "There are {$overview['products_count']} products.";

            return $this->localResult($answer, 'get_database_overview');
        }

        if ($this->containsAny($text, ['show all products', 'list products', 'all products', 'اعرض المنتجات', 'كل المنتجات', 'قائمة المنتجات'])) {
            $result = $this->dataTools->execute('search_products', [
                'query' => null,
                'min_price' => null,
                'max_price' => null,
                'min_quantity' => null,
                'max_quantity' => null,
                'sort_by' => 'id',
                'direction' => 'asc',
                'limit' => 20,
            ], $user);

            return $this->localResult($this->formatProducts($result['records'], $arabic), 'search_products');
        }

        $searchTerm = $this->extractSearchTerm($message);

        if ($searchTerm !== null) {
            $result = $this->dataTools->execute('search_products', [
                'query' => $searchTerm,
                'min_price' => null,
                'max_price' => null,
                'min_quantity' => null,
                'max_quantity' => null,
                'sort_by' => 'name',
                'direction' => 'asc',
                'limit' => 10,
            ], $user);

            if (($result['records'] ?? []) !== []) {
                return $this->localResult($this->formatProducts($result['records'], $arabic), 'search_products');
            }
        }

        return $this->localResult(
            $arabic
                ? 'مش فاهم السؤال بدقة. اكتبي مثلًا: كام منتج موجود؟ أو اعرض كل المنتجات أو إيه أغلى منتج؟'
                : 'I could not understand the question precisely. Try: How many products are there? Show all products. What is the most expensive product?',
            $tool
        );
    }

    private function extremeProduct(User $user, bool $arabic, string $direction): array
    {
        $result = $this->dataTools->execute('search_products', [
            'query' => null,
            'min_price' => null,
            'max_price' => null,
            'min_quantity' => null,
            'max_quantity' => null,
            'sort_by' => 'price',
            'direction' => $direction,
            'limit' => 1,
        ], $user);

        $product = $result['records'][0] ?? null;

        if (! $product) {
            return $this->localResult($arabic ? 'لا توجد منتجات في قاعدة البيانات.' : 'There are no products in the database.', 'search_products');
        }

        $label = $direction === 'desc'
            ? ($arabic ? 'أغلى منتج' : 'The most expensive product')
            : ($arabic ? 'أرخص منتج' : 'The cheapest product');

        return $this->localResult(
            "{$label}: {$product['name']} — {$product['price']} (الكمية/quantity: {$product['quantity']}).",
            'search_products'
        );
    }

    private function formatProducts(array $products, bool $arabic): string
    {
        if ($products === []) {
            return $arabic ? 'لا توجد منتجات مطابقة.' : 'No matching products were found.';
        }

        $lines = array_map(
            fn (array $product) => "#{$product['id']} - {$product['name']} | ".
                ($arabic ? 'السعر' : 'price').": {$product['price']} | ".
                ($arabic ? 'الكمية' : 'quantity').": {$product['quantity']}",
            $products
        );

        return ($arabic ? "المنتجات:\n" : "Products:\n").implode("\n", $lines);
    }

    private function extractSearchTerm(string $message): ?string
    {
        if (preg_match('/["\']([^"\']{2,60})["\']/u', $message, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:search(?: for)?|find|ابحث عن|دور على|منتج اسمه)\s+(.{2,60})$/iu', trim($message), $matches)) {
            return trim($matches[1], " ?!.,\t\n\r\0\x0B");
        }

        return null;
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function localResult(string $answer, string $tool): array
    {
        return [
            'answer' => $answer,
            'model' => 'local-database-chatbot',
            'tools_used' => [$tool],
        ];
    }

    private function instructions(User $user): string
    {
        $access = $user->isAdmin()
            ? 'The user is an admin and may access product and safe user statistics tools.'
            : 'The user is not an admin. Never claim access to user records or admin-only data.';

        return <<<PROMPT
You are the data assistant inside a Laravel products project.
Answer questions using the provided database tools whenever the answer depends on project data.
Never invent product counts, prices, quantities, user counts, or database records.
Do not request or expose passwords, password hashes, API tokens, remember tokens, or secret keys.
Use the same language as the user's latest message.
Be direct and include the relevant numbers or matching records.
If the requested data is outside the available tools, clearly say that the project does not expose that data yet.
{$access}
PROMPT;
    }

    private function extractOutputText(array $output): string
    {
        $parts = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    $parts[] = (string) ($content['text'] ?? '');
                }
            }
        }

        return trim(implode("\n", array_filter($parts)));
    }
}

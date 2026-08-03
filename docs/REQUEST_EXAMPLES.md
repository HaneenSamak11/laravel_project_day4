# API Request Examples

## Register

```json
{
  "name": "Mahmoud",
  "email": "mahmoud@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "Postman"
}
```

## Login

```json
{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "Postman"
}
```

## Create Product — Admin token

```json
{
  "name": "Laptop",
  "description": "Gaming laptop",
  "price": 25000,
  "quantity": 5
}
```

## Filter Products

```text
GET /api/products?search=laptop&min_price=1000&max_price=30000&in_stock=1&sort_by=price&direction=desc
```

## Ask Data Chatbot

```json
{
  "message": "Show me the three most expensive products and their quantities."
}
```

For protected endpoints:

```text
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

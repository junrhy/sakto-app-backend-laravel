import { useState } from 'react';

type Endpoint = 'list-sales' | 'create-sale' | 'get-sale' | 'list-items' | 'create-item' | 'update-item' | 'delete-item';

// Add base URL constant
const BASE_URL = 'http://api.sakto.app/api';

interface ApiExampleProps {
    endpoint: Endpoint;
}

interface Example {
    request: string;
    response: string;
}

type Examples = {
    [key in Endpoint]: Example;
};

export const ApiExample = ({ endpoint }: ApiExampleProps) => {
    const examples: Examples = {
        'list-sales': {
            request: `curl -X GET \\
  '${BASE_URL}/retail-sales?page=1&per_page=10' \\
  -H 'Authorization: Bearer {your_token}'`,
            response: `{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 123,
                "items": [
                    {
                        "item_id": 1,
                        "name": "Product Name",
                        "quantity": 2,
                        "price": 19.99,
                        "subtotal": 39.98
                    }
                ],
                "payment_method": "cash",
                "total_amount": 39.98,
                "created_at": "2024-03-19T08:30:00Z"
            }
        ],
        "per_page": 10,
        "total": 50
    }
}`
        },
        'create-sale': {
            request: `curl -X POST \\
  '${BASE_URL}/retail-sales' \\
  -H 'Authorization: Bearer {your_token}' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "items": [
        {
            "item_id": 1,
            "quantity": 2,
            "price": 19.99
        }
    ],
    "payment_method": "cash",
    "total_amount": 39.98
}'`,
            response: `{
    "status": "success",
    "data": {
        "id": 123,
        "items": [
            {
                "item_id": 1,
                "name": "Product Name",
                "quantity": 2,
                "price": 19.99,
                "subtotal": 39.98
            }
        ],
        "payment_method": "cash",
        "total_amount": 39.98,
        "created_at": "2024-03-19T08:30:00Z"
    }
}`
        },
        'get-sale': {
            request: `curl -X GET \\
  '${BASE_URL}/retail-sales/123' \\
  -H 'Authorization: Bearer {your_token}'`,
            response: `{
    "status": "success",
    "data": {
        "id": 123,
        "items": [
            {
                "item_id": 1,
                "name": "Product Name",
                "quantity": 2,
                "price": 19.99,
                "subtotal": 39.98
            }
        ],
        "payment_method": "cash",
        "total_amount": 39.98,
        "created_at": "2024-03-19T08:30:00Z"
    }
}`
        },
        'list-items': {
            request: `curl -X GET \\
  '${BASE_URL}/inventory?page=1&per_page=10' \\
  -H 'Authorization: Bearer {your_token}'`,
            response: `{
    "status": "success",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Product Name",
                "description": "Product description",
                "price": 19.99,
                "stock": 100,
                "created_at": "2024-03-19T08:30:00Z",
                "updated_at": "2024-03-19T08:30:00Z"
            }
        ],
        "per_page": 10,
        "total": 50
    }
}`
        },
        'create-item': {
            request: `curl -X POST \\
  '${BASE_URL}/inventory' \\
  -H 'Authorization: Bearer {your_token}' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "New Product",
    "description": "Product description",
    "price": 19.99,
    "stock": 100
}'`,
            response: `{
    "status": "success",
    "data": {
        "id": 1,
        "name": "New Product",
        "description": "Product description",
        "price": 19.99,
        "stock": 100,
        "created_at": "2024-03-19T08:30:00Z",
        "updated_at": "2024-03-19T08:30:00Z"
    }
}`
        },
        'update-item': {
            request: `curl -X PUT \\
  '${BASE_URL}/inventory/1' \\
  -H 'Authorization: Bearer {your_token}' \\
  -H 'Content-Type: application/json' \\
  -d '{
    "name": "Updated Product",
    "description": "Updated description",
    "price": 29.99,
    "stock": 150
}'`,
            response: `{
    "status": "success",
    "data": {
        "id": 1,
        "name": "Updated Product",
        "description": "Updated description",
        "price": 29.99,
        "stock": 150,
        "created_at": "2024-03-19T08:30:00Z",
        "updated_at": "2024-03-19T09:15:00Z"
    }
}`
        },
        'delete-item': {
            request: `curl -X DELETE \\
  '${BASE_URL}/inventory/1' \\
  -H 'Authorization: Bearer {your_token}'`,
            response: `{
    "status": "success",
    "message": "Item deleted successfully"
}`
        }
    };

    return (
        <div className="space-y-6">
            {/* Sample Request */}
            <div className="bg-gray-100 dark:bg-zinc-800 rounded-lg overflow-hidden">
                <div className="px-4 py-2 bg-gray-200 dark:bg-zinc-700">
                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Sample Request</h4>
                </div>
                <div className="p-4">
                    <pre className="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto">
                        {examples[endpoint]?.request || 'Select an endpoint'}
                    </pre>
                </div>
            </div>

            {/* Sample Response */}
            <div className="bg-gray-100 dark:bg-zinc-800 rounded-lg overflow-hidden">
                <div className="px-4 py-2 bg-gray-200 dark:bg-zinc-700">
                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Sample Response</h4>
                </div>
                <div className="p-4">
                    <pre className="text-sm text-gray-800 dark:text-gray-200 overflow-x-auto">
                        {examples[endpoint]?.response || '{}'}
                    </pre>
                </div>
            </div>

            {/* Status Codes */}
            <div className="bg-gray-100 dark:bg-zinc-800 rounded-lg overflow-hidden">
                <div className="px-4 py-2 bg-gray-200 dark:bg-zinc-700">
                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Status Codes</h4>
                </div>
                <div className="p-4">
                    <div className="space-y-2">
                        <div className="flex justify-between">
                            <code className="text-green-600">200</code>
                            <span className="text-sm text-gray-600 dark:text-gray-300">Success</span>
                        </div>
                        <div className="flex justify-between">
                            <code className="text-red-600">400</code>
                            <span className="text-sm text-gray-600 dark:text-gray-300">Bad Request</span>
                        </div>
                        <div className="flex justify-between">
                            <code className="text-red-600">401</code>
                            <span className="text-sm text-gray-600 dark:text-gray-300">Unauthorized</span>
                        </div>
                        <div className="flex justify-between">
                            <code className="text-red-600">404</code>
                            <span className="text-sm text-gray-600 dark:text-gray-300">Not Found</span>
                        </div>
                        <div className="flex justify-between">
                            <code className="text-red-600">500</code>
                            <span className="text-sm text-gray-600 dark:text-gray-300">Server Error</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}; 
<?php

namespace App\Controller;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/../../graphql/GraphQLResolver.php';
require_once __DIR__ . '/../../config/database.php';

class GraphQL {
    static public function handle() {
        try {
            $resolver = new \GraphQLResolver();

            // Define Product Type
            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => [
                    'id' => ['type' => Type::id()],
                    'name' => ['type' => Type::string()],
                    'price' => ['type' => Type::float()],
                    'image' => ['type' => Type::string()],
                    'brand' => ['type' => Type::string()],
                    'description' => ['type' => Type::string()],
                    'inStock' => ['type' => Type::boolean()],
                    'category_id' => ['type' => Type::int()],
                    'amount' => ['type' => Type::float()],
                    'image_url' => ['type' => Type::string()],
                    'attributes' => [
                        'type' => Type::string(),
                        'resolve' => static function ($product) use ($resolver) {
                            $productId = $product['original_id'] ?? $product['id'];
                            return json_encode($resolver->getAttributes(['id' => $productId]));
                        }
                    ],
                ],
            ]);

            // Define Cart Type
            $cartType = new ObjectType([
                'name' => 'Cart',
                'fields' => [
                    'id' => ['type' => Type::id()],
                    'product' => ['type' => $productType],
                    'quantity' => ['type' => Type::int()],
                ],
            ]);

            // Define OrderResult Type
            $orderResultType = new ObjectType([
                'name' => 'OrderResult',
                'fields' => [
                    'success' => ['type' => Type::boolean()],
                    'message' => ['type' => Type::string()],
                ],
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'cart' => [
                        'type' => Type::listOf($cartType),
                        'resolve' => static fn () => $resolver->getCart(),
                    ],
                    'products' => [
                        'type' => Type::listOf($productType),
                        'resolve' => static fn () => $resolver->getProducts(),
                    ],
                    'product' => [
                        'type' => $productType,
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::id())],
                        ],
                        'resolve' => static function ($rootValue, array $args) use ($resolver) {
                            $product = $resolver->getProduct($args);
                            if ($product) {
                                $product['original_id'] = $args['id']; // Store the original query ID
                            }
                            return $product;
                        },
                    ],
                    'attributes' => [
                        'type' => Type::string(), // This will return a JSON string of attributes
                        'args' => [
                            'id' => ['type' => Type::nonNull(Type::id())],
                        ],
                        'resolve' => static fn ($rootValue, array $args) => json_encode($resolver->getAttributes($args)),
                    ],
                ],
            ]);

            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    'addToCart' => [
                        'type' => $cartType,
                        'args' => [
                            'productId' => ['type' => Type::nonNull(Type::id())],
                            'quantity' => ['type' => Type::nonNull(Type::int())],
                        ],
                        'resolve' => static fn ($rootValue, array $args) => $resolver->addToCart($args),
                    ],
                    'updateCart' => [
                        'type' => $cartType,
                        'args' => [
                            'itemId' => ['type' => Type::nonNull(Type::id())],
                            'quantityChange' => ['type' => Type::nonNull(Type::int())],
                        ],
                        'resolve' => static fn ($rootValue, array $args) => $resolver->updateCartItem($args),
                    ],
                    'removeFromCart' => [
                        'type' => $cartType,
                        'args' => [
                            'itemId' => ['type' => Type::nonNull(Type::id())],
                        ],
                        'resolve' => static fn ($rootValue, array $args) => $resolver->removeFromCart($args),
                    ],
                    'placeOrder' => [
                        'type' => $orderResultType,
                        'resolve' => static fn () => $resolver->placeOrder(),
                    ],
                ],
            ]);

            // See docs on schema options:
            // https://webonyx.github.io/graphql-php/schema-definition/#configuration-options
            $schema = new Schema(
                (new SchemaConfig())
                ->setQuery($queryType)
                ->setMutation($mutationType)
            );

            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to get php://input');
            }

            $input = json_decode($rawInput, true);
            if (!$input || !isset($input['query'])) {
                throw new RuntimeException('Invalid GraphQL request');
            }

            $query = $input['query'];
            $variableValues = $input['variables'] ?? null;

            $rootValue = ['prefix' => 'You said: '];
            $result = GraphQLBase::executeQuery($schema, $query, $rootValue, null, $variableValues);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output);
    }
}
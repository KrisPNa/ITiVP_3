<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-Key");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/Auth.php';
require_once 'models/CartModel.php';

$apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$auth = new Auth();
$userId = $auth->authenticate($apiKey);

if (!$userId) {
    http_response_code(401);
    echo json_encode(["message" => "Доступ запрещен. Неверный или отсутствующий API-ключ"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

$id = null;
if (preg_match('/\/api\/carts\/(\d+)$/', $request_uri, $matches)) {
    $id = intval($matches[1]);
}

if (strpos($request_uri, '/api/carts') === false) {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint не найден"]);
    exit();
}

$cartModel = new CartModel();

function validateCartData($data, $isUpdate = false) {
    $errors = [];
    
    if (!$isUpdate || isset($data['session_id'])) {
        if (empty($data['session_id'])) {
            $errors[] = "session_id обязателен";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{1,255}$/', $data['session_id'])) {
            $errors[] = "session_id должен содержать только буквы, цифры и подчеркивания (1-255 символов)";
        }
    }
    
    if (!$isUpdate || isset($data['product_id'])) {
        if (empty($data['product_id'])) {
            $errors[] = "product_id обязателен";
        } elseif (!filter_var($data['product_id'], FILTER_VALIDATE_INT) || $data['product_id'] <= 0) {
            $errors[] = "product_id должен быть положительным целым числом";
        }
    }
    
    if (!$isUpdate || isset($data['quantity'])) {
        if (empty($data['quantity'])) {
            $errors[] = "quantity обязателен";
        } elseif (!filter_var($data['quantity'], FILTER_VALIDATE_INT) || $data['quantity'] <= 0) {
            $errors[] = "quantity должен быть положительным целым числом";
        }
    }
    
    return $errors;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $cartModel->getCartItem($id);
                if ($stmt->rowCount() > 0) {
                    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
                    http_response_code(200);
                    echo json_encode($cartItem);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Элемент корзины не найден"]);
                }
            } else {
                $session_id = isset($_GET['session_id']) ? $_GET['session_id'] : '';
                
                if (empty($session_id)) {
                    $stmt = $cartModel->getAllCarts();
                    $num = $stmt->rowCount();
                    
                    if ($num > 0) {
                        $carts_arr = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $carts_arr[] = $row;
                        }
                        http_response_code(200);
                        echo json_encode($carts_arr);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Корзина пуста"]);
                    }
                } else {
                    if (!preg_match('/^[a-zA-Z0-9_]{1,255}$/', $session_id)) {
                        http_response_code(400);
                        echo json_encode(["message" => "Неверный формат session_id"]);
                        break;
                    }
                    
                    $stmt = $cartModel->getCart($session_id);
                    $num = $stmt->rowCount();
                    
                    if ($num > 0) {
                        $carts_arr = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $carts_arr[] = $row;
                        }
                        http_response_code(200);
                        echo json_encode($carts_arr);
                    } else {
                        http_response_code(404);
                        echo json_encode(["message" => "Корзина пуста для session_id: " . $session_id]);
                    }
                }
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $errors = validateCartData($data, false);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(["message" => "Ошибки валидации", "errors" => $errors]);
                break;
            }
            
            $session_id = $data['session_id'];
            $product_id = intval($data['product_id']);
            $quantity = intval($data['quantity']);
            
            $newId = $cartModel->createCartItem($session_id, $product_id, $quantity);
            
            if ($newId) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Элемент корзины создан",
                    "id" => $newId
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Ошибка при создании элемента корзины"]);
            }
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["message" => "Не указан ID элемента для обновления"]);
                break;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            $errors = validateCartData($data, true);
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(["message" => "Ошибки валидации", "errors" => $errors]);
                break;
            }
            
            $session_id = $data['session_id'];
            $product_id = intval($data['product_id']);
            $quantity = intval($data['quantity']);
            
            if ($cartModel->updateCartItem($id, $session_id, $product_id, $quantity)) {
                http_response_code(200);
                echo json_encode(["message" => "Элемент корзины обновлен"]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Ошибка при обновлении элемента корзины"]);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["message" => "Не указан ID элемента для удаления"]);
                break;
            }
            
            if ($cartModel->deleteCartItem($id)) {
                http_response_code(200);
                echo json_encode(["message" => "Элемент корзины удален"]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Ошибка при удалении элемента корзины"]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["message" => "Метод не поддерживается"]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Внутренняя ошибка сервера", "error" => $e->getMessage()]);
}
?>
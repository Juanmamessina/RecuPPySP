<?php

require_once '../app/Modelos/Producto.php';


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;

class ControladorProducto 
{
    public function crearProducto($request, $response, $args) {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $imagen = $uploadedFiles['imagen'];

        $producto = new Producto();

        $mensaje = $producto->insertarProducto(
            $data['marca'], 
            $data['precio'], 
            $data['stock'], 
            $data['tipo'], 
            $data['color'], 
            $data['modelo'], 
            $imagen
        );

        if ($mensaje === "Producto guardado exitosamente.") {
            $response->getBody()->write(json_encode(["message" => $mensaje]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(["message" => $mensaje]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function listarProductos($request, $response, $args) {
        $producto = new Producto(); 
        $stmt = $producto->leerProductos(); 
        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($productos)); 
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function consultarProducto($request, $response, $args) {
        $data = $request->getParsedBody();
        $marca = $data['marca'];
        $tipo = $data['tipo'];
        $color = $data['color'];

        $producto = new Producto();
        $mensaje = $producto->consultarProducto($marca, $tipo, $color);

        $response->getBody()->write(json_encode(["message" => $mensaje]));
        return $response->withHeader('Content-Type', 'application/json');
    }

}

<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
require_once '../app/Modelos/Venta.php';
date_default_timezone_set('America/Buenos_Aires');
class ControladorVenta 
{
    public function manejarSolicitud($request, $response, $args) 
    {
        $solicitud = $request->getMethod();
        
        if ($solicitud == "POST") {
            return $this->altaVenta($request, $response, $args);
        } 
        elseif ($solicitud == "PUT") {

            return $this->modificarVenta($request, $response, $args);
        } 
        else 
        {
            return $response->withStatus(405); 
        }
    }

    public function altaVenta($request, $response, $args) {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $imagen = $uploadedFiles['imagen'];

        $venta = new Venta();

        $mensaje = $venta->insertarVenta(
            $data['email_usuario'],
            $data['marca'],
            $data['tipo'],
            $data['modelo'],
            $data['cantidad'],
            $imagen
        );

        if (($mensaje == 'Venta registrada exitosamente') !== false) {
            $response->getBody()->write(json_encode(["message" => $mensaje]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([$mensaje]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }


    public function modificarVenta($request, $response, $args)
    {
        $body = $request->getBody()->getContents();
        $input = json_decode($body, true); 
        
        
        $numeroPedido = $input['numero_pedido'] ?? null;
        $email = $input['email'] ?? null;
        $marca = $input['marca'] ?? null;
        $tipo = $input['tipo'] ?? null;
        $modelo = $input['modelo'] ?? null;
        $cantidad = $input['cantidad'] ?? null;
    
        if ($numeroPedido && $email && $marca && $tipo && $modelo && $cantidad) {
            $resultado = Venta::modificarVenta($numeroPedido, $email, $marca, $tipo, $modelo, $cantidad);
            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(["message" => "Faltan datos en la solicitud."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
    


    public function consultarVentasPorFecha($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $fecha = isset($parametros['fecha']) ? $parametros['fecha'] : null;

        $consulta = Venta::consultarVentasPorFecha($fecha);
        $response->getBody()->write($consulta);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function consultarVentasPorUsuario($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $usuario = isset($parametros['email_usuario']) ? $parametros['email_usuario'] : null;
    
        $consulta = Venta::consultarVentasPorUsuario($usuario);
    
        
        if ($consulta) {
            $data = json_encode($consulta); 
            $response->getBody()->write($data); 
            return $response->withHeader('Content-Type', 'application/json'); 
        } else {
            
            $response->getBody()->write(json_encode(['error' => 'No se encontraron ventas para el usuario especificado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404); 
        }
    }
    

    public function consultarVentasPorTipoProducto($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $tipoProducto = isset($parametros['tipoProducto']) ? $parametros['tipoProducto'] : null;
    
        $consulta = Venta::consultarVentasPorTipoProducto($tipoProducto);
    
        
        if ($consulta !== false) {
            
            $data = json_encode($consulta);
    
            $response->getBody()->write($data);
    
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron ventas para el tipo de producto especificado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
    

    public function consultarProductosPorPrecio($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $precioMin = isset($parametros['precioMin']) ? $parametros['precioMin'] : null;
        $precioMax = isset($parametros['precioMax']) ? $parametros['precioMax'] : null;
    
        $consulta = Venta::consultarProductosPorPrecio($precioMin, $precioMax);
    
        if ($consulta !== false) {
            
            $data = json_encode($consulta);
    
            $response->getBody()->write($data);
    
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'No se encontraron productos dentro del rango de precios especificado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
    

    public function consultarIngresos($request, $response, $args)
    {
        $parametros = $request->getQueryParams();
        $fecha = isset($parametros['fecha']) ? $parametros['fecha'] : null;

        $consulta = Venta::consultarIngresos($fecha);
        $response->getBody()->write($consulta);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function consultarProductoMasVendido($request, $response, $args)
    {
        $consulta = Venta::consultarProductoMasVendido();
        $response->getBody()->write($consulta);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function exportarArchivosCSV(Request $request, Response $response, $args) {
        try {
            $productos = new Venta();
            $csv = $productos->exportarArchivoCSV();

            $response->getBody()->write($csv);
            return $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="exportacion.csv"')
                ->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withHeader('Content-Type', 'text/plain')->withStatus(500);
        }
    }


}



?>

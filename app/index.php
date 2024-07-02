<?php
//php -S localhost:888 -t app

// "firebase/php-jwt": "^4.0"
// composer require firebase/php-jwt

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandlerInterface;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Response as ResponseClass;
use Dotenv\Dotenv;


require_once '../vendor/autoload.php';
require_once '../app/Controladores/ControladorUsuarios.php';
require_once '../app/Controladores/ControladorProductos.php';
require_once '../app/Controladores/ControladorVenta.php';
require_once '../app/Middlewares/ConfirmarPerfil.php';
require_once '../app/Middlewares/VerificarCampos.php';
require_once '../app/AuthJWT.php';



$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();


$app = AppFactory::create();


$app->get('/hello', function (Request $request, Response $response, array $args) {
		$response->getBody()->write("Funciona");
return $response;
});




$app->group('/tienda', function(RouteCollectorProxy $group){
    $group->post('/alta', function(Request $request, Response $response, $args) {
        $controlador = new ControladorProducto();
        return $controlador->crearProducto($request, $response, $args);
    });
    $group->post('/consultar', function(Request $request, Response $response, $args){
        $controlador = new ControladorProducto();
        return $controlador->consultarProducto($request, $response, $args);
    });
    $group->put('/ventas/modificar', function(Request $request, Response $response, $args){
        $controlador = new ControladorVenta();
        return $controlador->manejarSolicitud($request, $response, $args);
    });
    $group->get('/ventas/descargar', function(Request $request, Response $response, $args){
        $controlador = new ControladorVenta();
        return $controlador->exportarArchivosCSV($request, $response, $args);
    });
})->add(new MWConfirmarPerfil(['admin']));






$app->group('/tienda', function(RouteCollectorProxy $group) {
    $group->post('/ventas/alta', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->altaVenta($request, $response, $args);
    });

    
    $group->get('/consultar/ventas/porUsuario', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarVentasPorUsuario($request, $response, $args);
    })->add(new VerificarCamposMW(['email_usuario']));

    $group->get('/consultar/ventas/porProducto', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarVentasPorTipoProducto($request, $response, $args);
    })->add(new VerificarCamposMW(['tipoProducto']));

    
    $group->get('/consultar/productos/vendidos', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarVentasPorFecha($request, $response, $args);
    });

    
    $group->get('/consultar/productos/entreValores', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarProductosPorPrecio($request, $response, $args);
    })->add(new VerificarCamposMW(['precioMin', 'precioMax']));

    
    $group->get('/consultar/productos/masVendido', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarProductoMasVendido($request, $response, $args);
    });


    $group->get('/consultar/ingresos', function(Request $request, Response $response, $args) {
        $controlador = new ControladorVenta();
        return $controlador->consultarIngresos($request, $response, $args);
    });


})->add(new MWConfirmarPerfil(['admin', 'empleado']));




$app->post('/registro', function (Request $request, Response $response, array $args)  
{
    $controlador = new ControladorUsuario();
    return $controlador->crearUsuario($request, $response, $args);
});




$app->post('/login', function (Request $request, Response $response, array $args)  
{
    $controlador = new ControladorUsuario();
    return $controlador->iniciarSesion($request, $response, $args);
});



$app->run();
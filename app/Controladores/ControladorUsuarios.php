<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
require_once '../app/Modelos/Usuario.php';

class ControladorUsuario 
{

    public function crearUsuario(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        $perfil = isset($data['perfil']) ? $data['perfil'] : '';
        if (!in_array($perfil, Usuario::$perfilesValidos)) {
            $response->getBody()->write(json_encode(['error' => 'Perfil de usuario no valido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $imagen = isset($uploadedFiles['foto']) ? $uploadedFiles['foto'] : null;
        if ($imagen === null || $imagen->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Error al subir la imagen.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        try {
            
            $usuario = new Usuario();
            $mensaje = $usuario->insertarUsuario(
                $data['mail'],
                $data['usuario'],
                $data['password'],
                $perfil,
                $imagen
            );

            if ($mensaje === "Los datos se han agregado correctamente a la base de datos.") {
                $response->getBody()->write(json_encode(['mensaje' => 'Usuario creado exitosamente.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode(['error' => $mensaje]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Error al insertar usuario: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    
    public function listarUsuarios($request, $response, $args) {
        $usuario = new Usuario(); 
        $stmt = $usuario->leerUsuarios(); 
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $response->getBody()->write(json_encode($usuarios)); 
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function iniciarSesion($request, $response, $args) 
    {
        $parametros = $request->getParsedBody();
        $usuario = $parametros['usuario'];
        $contrasena = $parametros['password'];
        $perfil = $parametros['perfil'];

        $usuarioValido = Usuario::validarUsuario($usuario, $contrasena);

        if ($usuarioValido) {
            $datos = array('usuario' => $usuarioValido->usuario, 'perfil' => $perfil);
            $token = AutentificadorJWT::CrearToken($datos);
            $payload = json_encode(array('jwt' => $token));
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(array('error' => 'Usuario o clave incorrectos')));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    public function descargarPDFUsuarios(Request $request, Response $response, $args)
    {
        $usuarioModel = new Usuario();
        $usuarioModel->generarPDFUsuarios();
        $response->getBody()->write(json_encode($usuarioModel));
        return $response->withHeader('Content-Type', 'application/json');
    }





    
}   

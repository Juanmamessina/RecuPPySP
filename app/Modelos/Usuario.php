<?php

require_once '../app/DB.php';

class Usuario {
    private $conexion;
    private $nombre_tabla = "usuarios";

    public $usuario;
    public $contrasena;
    public $perfil;
    public $foto;
    public $fecha_baja;
    public static $perfilesValidos = ['cliente', 'empleado', 'admin'];



    public function insertarUsuario($mail, $usuario, $contrasena, $perfil, $foto) 
    {
        try {

            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $rutaImagenes = __DIR__ . "/../../ImagenesDeUsuarios/2024";
            if (!file_exists($rutaImagenes)) {
                mkdir($rutaImagenes, 0777, true);
            }

            $nombreArchivo = "{$usuario}_{$perfil}." . pathinfo($foto->getClientFilename(), PATHINFO_EXTENSION);
            $rutaCompleta = $rutaImagenes . DIRECTORY_SEPARATOR . $nombreArchivo;

            
            $foto->moveTo($rutaCompleta);

            $sql = "INSERT INTO usuarios (mail, usuario, contrasena, perfil, foto) VALUES (:mail, :usuario, :contrasena, :perfil, :foto)";
            
            $stmt = $conexion->prepare($sql);

            $stmt->bindParam(':mail', $mail);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':contrasena', $contrasena);
            $stmt->bindParam(':perfil', $perfil);
            $stmt->bindParam(':foto', $nombreArchivo);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return "Los datos se han agregado correctamente a la base de datos.";
            } else {
                return "No se han podido agregar los datos a la base de datos.";
            }
        } catch (PDOException $error) {
            return "Error: " . $error->getMessage();
        }
    }

    public static function validarUsuario($usuario, $contrasena) 
    {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_OBJ);

            if ($usuario) {
        
                if ($contrasena === $usuario->contrasena) 
                {
                    return $usuario;
                } 
                else 
                {
                    return false;
                }
            } else 
            {
                
                return false;
            }
        } catch (PDOException $error) {
            return "Error: " . $error->getMessage();
        }
    }




    

}

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

    public function leerUsuarios()
    {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $query = "SELECT * FROM usuarios";
            $stmt = $conexion->prepare($query);
            $stmt->execute();

            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $usuarios;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function generarPDFUsuarios()
    {
        
        

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Listado de Usuarios', 0, 1, 'C');
        
        $usuarios = $this->leerUsuarios();

        $pdf->Cell(15, 10, 'id', 1);
        $pdf->Cell(60, 10, 'mail', 1);
        $pdf->Cell(30, 10, 'usuario', 1);
        $pdf->Cell(15, 10, 'clave', 1);
        $pdf->Cell(20, 10, 'perfil', 1);
        $pdf->Cell(60, 10, 'foto', 1);
        $pdf->Cell(30, 10, 'fecha de alta', 1);
        $pdf->Ln();

        foreach ($usuarios as $usuario) {
            $pdf->Cell(15, 10, $usuario['id'], 1);
            $pdf->Cell(60, 10, $usuario['mail'], 1);
            $pdf->Cell(30, 10, $usuario['usuario'], 1);
            $pdf->Cell(15, 10, $usuario['contrasena'], 1);
            $pdf->Cell(20, 10, $usuario['perfil'], 1);

        }
           

          

        
    }




    

}

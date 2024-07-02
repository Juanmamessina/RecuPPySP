<?php

require_once '../app/DB.php';

class Producto {
    private $conexion;
    private $nombre_tabla = "productos";

    public $marca;
    public $precio;
    public $stock;
    public $tipo;
    public $fecha_registro;
    public $modelo;
    public $color;


    public function insertarProducto($marca, $precio, $stock, $tipo, $color, $modelo, $imagen) 
    {
        try 
        {
            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $sql = "SELECT * FROM productos WHERE marca = :marca AND tipo = :tipo";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->execute();

            $productoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($productoExistente) 
            {
                
                $nuevoStock = $productoExistente['stock'] + $stock;
                $sql = "UPDATE productos SET precio = :precio, stock = :stock, color = :color, modelo = :modelo, imagen = :imagen WHERE marca = :marca AND tipo = :tipo";
                $stmt = $conexion->prepare($sql);
                $stmt->bindParam(':precio', $precio);
                $stmt->bindParam(':stock', $nuevoStock);
                $stmt->bindParam(':color', $color);
                $stmt->bindParam(':modelo', $modelo);
                $stmt->bindParam(':marca', $marca);
                $stmt->bindParam(':tipo', $tipo);

                $imagenPath = $productoExistente['imagen'];
                if ($imagen && $imagen->getError() === UPLOAD_ERR_OK) {
                    $imagenDir = __DIR__ . "/../../ImagenesDeProductos/2024";
                    if (!file_exists($imagenDir)) {
                        mkdir($imagenDir, 0777, true);
                    }
                    $imagenPath = "$imagenDir/{$marca}_{$tipo}.jpg";
                    $imagen->moveTo($imagenPath);
                }
                $stmt->bindParam(':imagen', $imagenPath);

                $stmt->execute();

                return "Producto actualizado exitosamente.";
            } 
            else 
            {
                
                $fecha_registro = new DateTime();
                $fecha_registro_str = $fecha_registro->format('Y-m-d H:i:s');

                $sql = "INSERT INTO productos (marca, precio, stock, tipo, color, modelo, imagen, fecha_registro) VALUES (:marca, :precio, :stock, :tipo, :color, :modelo, :imagen, :fecha_registro)";
                $stmt = $conexion->prepare($sql);

                $stmt->bindParam(':marca', $marca);
                $stmt->bindParam(':precio', $precio);
                $stmt->bindParam(':stock', $stock);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':color', $color);
                $stmt->bindParam(':modelo', $modelo);
                $stmt->bindParam(':imagen', $imagenPath);
                $stmt->bindParam(':fecha_registro', $fecha_registro_str);

                $imagenPath = null;
                if ($imagen && $imagen->getError() === UPLOAD_ERR_OK) 
                {
                    $imagenDir = __DIR__ . "/../../ImagenesDeProductos/2024";
                    if (!file_exists($imagenDir)) 
                    {
                        mkdir($imagenDir, 0777, true);
                    }
                    $imagenPath = "$imagenDir/{$marca}_{$tipo}.jpg";
                    $imagen->moveTo($imagenPath);
                }

                $stmt->execute();

                if ($stmt->rowCount() > 0) 
                {
                    return "Producto guardado exitosamente.";
                } else 
                {
                    return "No se han podido agregar los datos a la base de datos.";
                }
            }
        } 
        catch (PDOException $e) 
        {
            return "Error: " . $e->getMessage();
        }
    }
    
    public function leerProductos() {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        $sql = "SELECT id, marca, precio, stock, color, modelo, imagen, fecha_registro FROM " . $this->nombre_tabla;
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        return $stmt;
    }


    public function consultarProducto($marca, $tipo, $color) {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $sql = "SELECT * FROM productos WHERE marca = :marca AND tipo = :tipo AND color = :color";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':color', $color);
            $stmt->execute();

            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($producto) {
                return "existe";
            }

            $sqlMarca = "SELECT * FROM productos WHERE marca = :marca";
            $stmtMarca = $conexion->prepare($sqlMarca);
            $stmtMarca->bindParam(':marca', $marca);
            $stmtMarca->execute();
            $marcaExiste = $stmtMarca->rowCount() > 0;

            $sqlTipo = "SELECT * FROM productos WHERE tipo = :tipo";
            $stmtTipo = $conexion->prepare($sqlTipo);
            $stmtTipo->bindParam(':tipo', $tipo);
            $stmtTipo->execute();
            $tipoExiste = $stmtTipo->rowCount() > 0;

            if (!$marcaExiste) {
                return "No hay productos de la marca $marca.";
            } elseif (!$tipoExiste) {
                return "No hay productos del tipo $tipo.";
            } else {
                return "No existe un producto con la combinacion de marca $marca, tipo $tipo y color $color.";
            }

        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function actualizarStockVenta($marca, $tipo, $modelo, $cantidadVendida) {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();
    
            $nuevoStock = $cantidadVendida; 
    
            $sqlUpdate = "UPDATE productos SET stock = stock - :cantidad_vendida WHERE marca = :marca AND tipo = :tipo AND modelo = :modelo";
            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':cantidad_vendida', $cantidadVendida);
            $stmtUpdate->bindParam(':marca', $marca);
            $stmtUpdate->bindParam(':tipo', $tipo);
            $stmtUpdate->bindParam(':modelo', $modelo);
            $stmtUpdate->execute();
    
            if ($stmtUpdate->rowCount() === 0) {
                throw new Exception("No se pudo actualizar el stock del producto.");
            }
    
            return true; 
    
        } catch (PDOException $e) {
            return false; 
        } catch (Exception $e) {
            return false; 
        }
    }

}

<?php

require_once '../app/DB.php';
require_once '../app/Modelos/Producto.php';

class Venta 
{
    private $conexion;
    private $modelo_tabla = "ventas";

    public $marca;
    public $precio;
    public $stock;
    public $tipo;
    public $fecha;
    public $modelo;
    public $cantidad;

    
    public function verificarStockDisponible($marca, $tipo, $modelo, $cantidad) 
    {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $sql = "SELECT stock FROM productos WHERE marca = :marca AND tipo = :tipo AND modelo = :modelo";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->execute();

            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto || $producto['stock'] < $cantidad) {
                return false;
            }

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

    public function generarNumeroPedido() 
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        $sql = "SELECT MAX(id) AS max_id FROM ventas";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimoId = $resultado['max_id'];

        $numeroPedido = $ultimoId + 1;

        return $numeroPedido;

    
    }
    
    

    public function insertarVenta($emailUsuario, $marca, $tipo, $modelo, $cantidad, $imagen) 
    {
        try 
        {

            $conexion = DB::obtenerInstancia()->obtenerConexion();

            $verificacion = $this->verificarCamposVenta([
                'email_usuario' => $emailUsuario,
                'marca' => $marca,
                'tipo' => $tipo,
                'modelo' => $modelo,
                'cantidad' => $cantidad
            ]);
    
            if ($verificacion !== true) 
            {
                return ["message" => $verificacion];
            }

            

            if (!$this->verificarStockDisponible($marca, $tipo, $modelo, $cantidad)) {
                return "No hay suficiente stock disponible para realizar la venta.";
            }

            $sqlPrecio = "SELECT precio FROM productos WHERE marca = :marca AND tipo = :tipo AND modelo = :modelo";
            $stmtPrecio = $conexion->prepare($sqlPrecio);
            $stmtPrecio->bindParam(':marca', $marca);
            $stmtPrecio->bindParam(':tipo', $tipo);
            $stmtPrecio->bindParam(':modelo', $modelo);
            $stmtPrecio->execute();
            $precioProducto = $stmtPrecio->fetchColumn();

            if ($precioProducto === false) {
                return "Error: Producto no encontrado.";
            }

            $montoTotal = $precioProducto * $cantidad;


            $fecha = date('Y-m-d');
            $numeroPedido = $this->generarNumeroPedido(); 

            
            $imagenDir = __DIR__ . "/../../ImagenesDeVenta/2024";

            
            $imagenName = "{$marca}_{$tipo}_{$modelo}_" . explode('@', $emailUsuario)[0] . "_" . date('YmdHis') . ".jpg";

            
            $imagenPath = "$imagenDir/$imagenName";
            $imagen->moveTo($imagenPath);


            $sql = "INSERT INTO ventas (email_usuario, marca, tipo, modelo, cantidad, monto, fecha, numero_pedido, imagen) 
                    VALUES (:email_usuario, :marca, :tipo, :modelo, :cantidad, :monto, :fecha, :numero_pedido, :imagen)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':email_usuario', $emailUsuario);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':monto', $montoTotal);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->bindParam(':numero_pedido', $numeroPedido);
            $stmt->bindParam(':imagen', $imagenName);
            $stmt->execute();

            $producto = new Producto();
            $producto->actualizarStockVenta($marca, $tipo, $modelo, $cantidad);

            return "Venta registrada exitosamente. Numero de pedido: $numeroPedido";

        } catch (PDOException $e) {
            return "Error al registrar la venta: " . $e->getMessage();
        }
    }

    private function verificarCamposVenta($campos) {
        $camposRequeridos = ['email_usuario', 'marca', 'tipo', 'modelo', 'cantidad'];
        foreach ($camposRequeridos as $campo) {
            if (empty($campos[$campo])) {
                return "El campo $campo esta incompleto.";
            }
        }
        return true;
    }
    


    

    public static function consultarVentasPorFecha($fecha)
    {

        $conexion = DB::obtenerInstancia()->obtenerConexion();

        if ($fecha === null) {
            $fecha = date('Y-m-d', strtotime('-1 day'));
        }

        $query = "SELECT SUM(cantidad) AS cantidad_vendida 
                FROM ventas 
                WHERE fecha = :fecha";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado !== false && isset($resultado['cantidad_vendida'])) {
            $cantidadVendida = (int)$resultado['cantidad_vendida'];
            $response = [
                "message" => "Cantidad de productos vendidos el $fecha",
                "cantidad_vendida" => $cantidadVendida
            ];
        } else {
            $response = [
                "message" => "No hay ventas registradas para la fecha $fecha"
            ];
        }
        return json_encode($response);
    }

    public static function consultarVentasPorUsuario($usuario)
    {

        $conexion = DB::obtenerInstancia()->obtenerConexion();

        $query = "SELECT * 
                FROM ventas 
                WHERE email_usuario = :usuario";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $ventasUsuario = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($ventasUsuario) {
            $response = [
                "message" => "Listado de ventas para el usuario $usuario",
                "ventas" => []
            ];
            foreach ($ventasUsuario as $venta) {
                $producto = $venta['tipo'];
                $cantidad = $venta['cantidad'];
                $response['ventas'][] = [
                    "producto" => $producto,
                    "cantidad" => $cantidad
                ];
            }
            return $response;
        } else {
            return ["message" => "No hay ventas registradas para el usuario $usuario"];
        }
    }

    public static function consultarVentasPorTipoProducto($tipoProducto)
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        $query = "SELECT SUM(cantidad) AS total_vendido 
                FROM ventas 
                WHERE tipo = :tipoProducto";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':tipoProducto', $tipoProducto);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado && isset($resultado['total_vendido'])) {
            $cantidadVendida = (int)$resultado['total_vendido'];
            $response = [
                "message" => "Listado de ventas para el tipo de producto $tipoProducto",
                "Ventas totales" => $cantidadVendida
            ];
            return $response;
        } else {
            return ["message" => "No hay ventas registradas para el tipo de producto $tipoProducto"];
        }
    }

    public static function consultarProductosPorPrecio($precioMin, $precioMax)
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        $query = "SELECT modelo, precio 
                FROM productos 
                WHERE precio BETWEEN :precio_min AND :precio_max";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':precio_min', $precioMin);
        $stmt->bindParam(':precio_max', $precioMax);
        $stmt->execute();
        $productosFiltrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($productosFiltrados) {
            $response = [
                "message" => "Listado de productos con precio entre $precioMin y $precioMax",
                "productos" => []
            ];

            foreach ($productosFiltrados as $producto) {
                $response['productos'][] = [
                    "modelo" => $producto['modelo'],
                    "precio" => $producto['precio']
                ];
            }

            return $response;
        } else {
            return ["message" => "No hay productos con precio dentro del rango especificado"];
        }
    }

    public static function consultarIngresos($fecha)
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        if ($fecha === null) {
            $query = "SELECT DATE(v.fecha) as fecha, SUM(v.cantidad * p.precio) AS ingresos 
                    FROM ventas v
                    JOIN productos p ON v.marca = p.marca AND v.tipo = p.tipo AND v.modelo = p.modelo
                    GROUP BY DATE(v.fecha)";
            $stmt = $conexion->prepare($query);
            $stmt->execute();
            $ingresosPorDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($ingresosPorDia) {
                $response = [
                    "message" => "Listado de ingresos por dia",
                    "ingresos" => []
                ];
                foreach ($ingresosPorDia as $ingreso) {
                    $response['ingresos'][] = [
                        "fecha" => $ingreso['fecha'],
                        "ingresos" => $ingreso['ingresos']
                    ];
                }
                return json_encode($response);
            } else {
                return json_encode(["message" => "No hay ingresos registrados para ninguna fecha"]);
            }
        } else {
            $query = "SELECT SUM(v.cantidad * p.precio) AS ingresos 
                    FROM ventas v
                    JOIN productos p ON v.marca = p.marca AND v.tipo = p.tipo AND v.modelo = p.modelo
                    WHERE DATE(v.fecha) = :fecha";

            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':fecha', $fecha);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado && $resultado['ingresos'] !== null) {
                $response = [
                    "message" => "Los ingresos generados el dia $fecha son de:",
                    "ingresos" => $resultado['ingresos']
                ];
                return json_encode($response);
            } else {
                return json_encode(["message" => "No hay ingresos registrados para la fecha $fecha"]);
            }
        }
    }

    public static function consultarProductoMasVendido()
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();
        
        $query = "SELECT marca, tipo, modelo, SUM(cantidad) AS total_vendido 
                FROM ventas 
                GROUP BY marca, tipo, modelo 
                ORDER BY total_vendido DESC 
                LIMIT 1";

        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $productoMasVendido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoMasVendido) {
            $productoMasVendido['total_vendido'] .= " unidades";
            $response = [
                "message" => "Producto mas vendido",
                "modelo" => [
                    "marca" => $productoMasVendido['marca'],
                    "tipo" => $productoMasVendido['tipo'],
                    "modelo" => $productoMasVendido['modelo'],
                    "total_vendido" => $productoMasVendido['total_vendido'] 
                ]
            ];
            return json_encode($response);
        } else {
            return json_encode(["message" => "No hay productos vendidos registrados."]);
        }
    }

    public static function modificarVenta($numeroPedido, $email, $marca, $tipo, $modelo, $cantidad)
    {
        try {
            $conexion = DB::obtenerInstancia()->obtenerConexion();
            
            $query = "SELECT * FROM ventas WHERE numero_pedido = :numero_pedido";
            $stmt = $conexion->prepare($query);
            $stmt->bindParam(':numero_pedido', $numeroPedido);
            $stmt->execute();
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($venta) {
                
                $updateQuery = "UPDATE ventas SET email_usuario = :email, marca = :marca, tipo = :tipo, modelo = :modelo, cantidad = :cantidad WHERE numero_pedido = :numero_pedido";
                $updateStmt = $conexion->prepare($updateQuery);
                $updateStmt->bindParam(':email', $email);
                $updateStmt->bindParam(':marca', $marca);
                $updateStmt->bindParam(':tipo', $tipo);
                $updateStmt->bindParam(':modelo', $modelo);
                $updateStmt->bindParam(':cantidad', $cantidad);
                $updateStmt->bindParam(':numero_pedido', $numeroPedido);
                $updateStmt->execute();

                return ["message" => "Venta modificada exitosamente."];
            } else {
                return ["message" => "No existe el número de pedido."];
            }
        } catch (PDOException $e) {
            return ["message" => "Error al modificar la venta: " . $e->getMessage()];
        }
    }




    public function leerVentas(){
        $conexion = DB::obtenerInstancia()->obtenerConexion();
        $query = "SELECT id, email_usuario, marca, tipo, modelo, cantidad, monto, fecha, numero_pedido FROM ventas";
        $consulta = $conexion->prepare($query);
        $consulta->execute();
        return $consulta;
    }
    
    public function exportarArchivoCSV(){

        $conexion = DB::obtenerInstancia()->obtenerConexion();
        $fh = fopen('php://temp', 'w+'); 

        if ($fh === false) {
            throw new Exception("Error al crear archivo CSV.");
        }
        
        fputcsv($fh, ['id', 'email_usuario', 'marca', 'tipo', 'modelo', 'cantidad', 'monto', 'fecha', 'numero_pedido']);
        $ventas = new Venta();
        $consulta = $ventas->leerVentas();
        $listaVentas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach($listaVentas as $venta){
            fputcsv($fh, $venta);
        }
        $csv = stream_get_contents($fh, -1, 0);
        fclose($fh);
        
        if ($csv === false) {
            throw new Exception("Error al leer el archivo CSV.");
        }
        return $csv;
    }

    ///////////////////////////////////////////////RECU

    public function generarArchivoPDF() {

        $listaVentas = $this->leerVentas();

        $pdf = new FPDF('L', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);

        $pdf->Cell(10, 5, 'ID', 1);
        $pdf->Cell(65, 5, 'Email Usuario', 1);
        $pdf->Cell(30, 5, 'Marca', 1);
        $pdf->Cell(30, 5, 'Tipo', 1);
        $pdf->Cell(30, 5, 'Modelo', 1);
        $pdf->Cell(20, 5, 'Cantidad', 1);
        $pdf->Cell(17, 5, 'Monto', 1);
        $pdf->Cell(30, 5, 'Fecha', 1);
        $pdf->Cell(30, 5, 'Numero Pedido', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        foreach ($listaVentas as $venta) {
            $pdf->Cell(10, 5, $venta['id'], 1);
            $pdf->Cell(65, 5, $venta['email_usuario'], 1);
            $pdf->Cell(30, 5, $venta['marca'], 1);
            $pdf->Cell(30, 5, $venta['tipo'], 1);
            $pdf->Cell(30, 5, $venta['modelo'], 1);
            $pdf->Cell(20, 5, $venta['cantidad'], 1);
            $pdf->Cell(17, 5, $venta['monto'], 1);
            $pdf->Cell(30, 5, $venta['fecha'], 1);
            $pdf->Cell(30, 5, $venta['numero_pedido'], 1);
            $pdf->Ln();
        }

        ob_start();
        $pdf->Output();
        $pdfContent = ob_get_clean();

        return $pdfContent;
    }


    public static function consultarProductoMenosVendido()
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();
        
        $query = "SELECT marca, tipo, modelo, SUM(cantidad) AS total_vendido 
                FROM ventas 
                GROUP BY marca, tipo, modelo 
                ORDER BY total_vendido ASC 
                LIMIT 1";

        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $productoMenosVendido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($productoMenosVendido) {
            $productoMenosVendido['total_vendido'] .= " unidades";
            $response = [
                "message" => "Producto menos vendido",
                "modelo" => [
                    "marca" => $productoMenosVendido['marca'],
                    "tipo" => $productoMenosVendido['tipo'],
                    "modelo" => $productoMenosVendido['modelo'],
                    "total_vendido" => $productoMenosVendido['total_vendido'] 
                ]
            ];
            return json_encode($response);
        } else {
            return json_encode(["message" => "No hay productos vendidos registrados."]);
        }
    }

    public static function obtenerProductosPorStock($orden)
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        if ($orden === 'asc') {
            $query = "SELECT * FROM productos ORDER BY stock ASC";
        } elseif ($orden === 'desc') {
            $query = "SELECT * FROM productos ORDER BY stock DESC";
        } else {
            $query = "SELECT * FROM productos ORDER BY stock ASC";
        }

        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $productos;
    }

    public static function obtenerProductosPorPrecio($orden)
    {
        $conexion = DB::obtenerInstancia()->obtenerConexion();

        if ($orden === 'asc') {
            $query = "SELECT * FROM productos ORDER BY precio ASC";
        } elseif ($orden === 'desc') {
            $query = "SELECT * FROM productos ORDER BY precio DESC";
        } else {
            $query = "SELECT * FROM productos ORDER BY precio ASC";
        }

        $stmt = $conexion->prepare($query);
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $productos;
    }




}









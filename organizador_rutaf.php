<?php
require_once 'rutaf.php';

class OrganizadorRutaF {
    private $db;
    private $rutaF;
    
    public function __construct($conn) {
        $this->db = $conn;
        $this->rutaF = new RutaF($conn);
    }
    
    public function reasignarOrdenesAutomaticamente() {
        try {
            pg_query($this->db, "BEGIN");
            
            $dias = $this->rutaF->obtenerDias();
            
            foreach ($dias as $dia) {
                $query = "UPDATE truta 
                         SET orden = subquery.new_orden
                         FROM (
                             SELECT 
                                 id_ruta,
                                 ROW_NUMBER() OVER (PARTITION BY id_dia ORDER BY orden, id_ruta) as new_orden
                             FROM truta 
                             WHERE id_dia = $1 AND baja = false
                         ) AS subquery
                         WHERE truta.id_ruta = subquery.id_ruta";
                
                pg_query_params($this->db, $query, [$dia['id_dia']]);
            }
            
            pg_query($this->db, "COMMIT");
            return true;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en reasignarOrdenesAutomaticamente: " . $e->getMessage());
            return false;
        }
    }
    
    public function obtenerRutasSemanalGlobal() {
        try {
            $dias = $this->rutaF->obtenerDias();
            $rutasSemana = [];
            
            foreach ($dias as $dia) {
                $rutasSemana[$dia['id_dia']] = [
                    'nombre_dia' => $dia['dia'],
                    'rutas' => $this->obtenerRutasPorDiaGlobal($dia['id_dia'])
                ];
            }
            
            return $rutasSemana;
            
        } catch (Exception $e) {
            error_log("Error en obtenerRutasSemanalGlobal: " . $e->getMessage());
            return [];
        }
    }
    
    public function obtenerRutasPorDiaGlobal($idDia) {
        try {
            $query = "SELECT 
                        r.id_ruta,
                        r.id_usuario,
                        r.id_dia,
                        r.id_cliente,
                        r.orden,
                        r.baja,
                        r.fechabaja,
                        u.nombre as nombre_usuario,
                        c.nombre as nombre_cliente,
                        c.telefono, 
                        c.direccion, 
                        c.cantidad_producto, 
                        dr.dia as nombre_dia
                      FROM truta r
                      LEFT JOIN tusuario u ON r.id_usuario = u.id_usuario
                      LEFT JOIN tcliente c ON r.id_cliente = c.id_cliente
                      LEFT JOIN tdia_ruta dr ON r.id_dia = dr.id_dia
                      WHERE r.id_dia = $1 AND r.baja = false
                      ORDER BY r.orden, r.id_ruta";
            
            $result = pg_query_params($this->db, $query, [$idDia]);
            
            if (!$result) {
                throw new Exception("Error al obtener rutas por día global: " . pg_last_error($this->db));
            }
            
            $rutas = [];
            while ($row = pg_fetch_assoc($result)) {
                $row['baja'] = $this->normalizarBooleano($row['baja']);
                $rutas[] = $row;
            }
            
            return $rutas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerRutasPorDiaGlobal: " . $e->getMessage());
            return [];
        }
    }
    
    public function moverRuta($idRuta, $nuevaPosicion) {
        try {
            pg_query($this->db, "BEGIN");
            
            $rutaActual = $this->rutaF->obtenerRutaPorId($idRuta);
            if (!$rutaActual) {
                throw new Exception("Ruta no encontrada");
            }
            
            $posicionActual = $rutaActual['orden'];
            $idDia = $rutaActual['id_dia'];
            
            if ($posicionActual == $nuevaPosicion) {
                pg_query($this->db, "COMMIT");
                return true;
            }
            
            if ($nuevaPosicion > $posicionActual) {
                $query = "UPDATE truta 
                         SET orden = orden - 1 
                         WHERE id_dia = $1 
                         AND orden > $2 
                         AND orden <= $3 
                         AND baja = false";
                pg_query_params($this->db, $query, [
                    $idDia, $posicionActual, $nuevaPosicion
                ]);
            } else {
                $query = "UPDATE truta 
                         SET orden = orden + 1 
                         WHERE id_dia = $1 
                         AND orden >= $2 
                         AND orden < $3 
                         AND baja = false";
                pg_query_params($this->db, $query, [
                    $idDia, $nuevaPosicion, $posicionActual
                ]);
            }
            
            $this->rutaF->update('truta', ['orden' => $nuevaPosicion], ['id_ruta' => $idRuta]);
            
            pg_query($this->db, "COMMIT");
            return true;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en moverRuta: " . $e->getMessage());
            throw new Exception("Error al mover la ruta: " . $e->getMessage());
        }
    }
    
    public function reordenarRutasDiaGlobal($idDia, $nuevoOrden) {
        try {
            
            pg_query($this->db, "BEGIN");
            
            foreach ($nuevoOrden as $posicion => $idRuta) {
                $orden = $posicion + 1;
                $this->rutaF->update('truta', ['orden' => $orden], [
                    'id_ruta' => $idRuta,
                    'id_dia' => $idDia
                ]);
            }
            
            pg_query($this->db, "COMMIT");
            return true;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en reordenarRutasDiaGlobal: " . $e->getMessage());
            throw new Exception("Error al reordenar rutas: " . $e->getMessage());
        }
    }
    
    public function obtenerEstadisticasRutasGlobal() {
        try {
            $query = "SELECT 
                        dr.id_dia,
                        dr.dia,
                        COUNT(r.id_ruta) as total_rutas,
                        MIN(r.orden) as minimo_orden,
                        MAX(r.orden) as maximo_orden
                      FROM tdia_ruta dr
                      LEFT JOIN truta r ON dr.id_dia = r.id_dia AND r.baja = false
                      GROUP BY dr.id_dia, dr.dia
                      ORDER BY dr.id_dia";
            
            $result = pg_query($this->db, $query);
            
            if (!$result) {
                throw new Exception("Error al obtener estadísticas globales: " . pg_last_error($this->db));
            }
            
            $estadisticas = [];
            while ($row = pg_fetch_assoc($result)) {
                $estadisticas[$row['id_dia']] = $row;
            }
            
            return $estadisticas;
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasRutasGlobal: " . $e->getMessage());
            return [];
        }
    }
    
    public function insertarRutaEnPosicionGlobal($rutaData, $posicionDeseada) {
        try {
            pg_query($this->db, "BEGIN");
            
            $query = "UPDATE truta 
                     SET orden = orden + 1 
                     WHERE id_dia = $1 
                     AND orden >= $2 
                     AND baja = false";
            pg_query_params($this->db, $query, [
                $rutaData['id_dia'], 
                $posicionDeseada
            ]);
            
            $rutaData['orden'] = $posicionDeseada;
            $idNuevaRuta = $this->rutaF->create('truta', $rutaData);
            
            pg_query($this->db, "COMMIT");
            return $idNuevaRuta;
            
        } catch (Exception $e) {
            pg_query($this->db, "ROLLBACK");
            error_log("Error en insertarRutaEnPosicionGlobal: " . $e->getMessage());
            throw new Exception("Error al insertar ruta: " . $e->getMessage());
        }
    }
    
    private function normalizarBooleano($valor) {
        if ($valor === 't' || $valor === true || $valor === 'true' || $valor === 1) {
            return true;
        }
        return false;
    }
}
?>
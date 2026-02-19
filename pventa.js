let carrito = [];
let totalVenta = 0;
let clienteSeleccionado = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('ID Usuario desde sesión:', ID_USUARIO_SESION);
    cargarProductos();
});

function cargarProductos(search = '') {
    fetch(`pventaf.php?action=buscarProductos&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(productos => {
            const grid = document.getElementById('productosGrid');
            grid.innerHTML = '';
            
            if (productos.error) {
                grid.innerHTML = `<div style="color: red;">Error: ${productos.error}</div>`;
                return;
            }
            
            productos.forEach(producto => {
                const card = document.createElement('div');
                card.className = 'producto-card';
                card.innerHTML = `
                    <strong>${producto.nombre}</strong>
                    <div>Precio: $${parseFloat(producto.precio).toFixed(2)}</div>
                    <div>Stock: ${producto.stock}</div>
                    <small>Código: ${producto.id_producto}</small>
                `;
                card.onclick = () => agregarAlCarrito(producto);
                grid.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error al cargar productos:', error);
            document.getElementById('productosGrid').innerHTML = `<div style="color: red;">Error de conexión</div>`;
        });
}

function buscarProducto() {
    const search = document.getElementById('searchProducto').value;
    cargarProductos(search);
}

function buscarClientes() {
    const search = document.getElementById('searchCliente').value;
    const suggestions = document.getElementById('clienteSuggestions');
    
    if (search.length < 2) {
        suggestions.style.display = 'none';
        return;
    }
    
    fetch(`pventaf.php?action=buscarClientes&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(clientes => {
            suggestions.innerHTML = '';
            
            if (clientes.error) {
                return;
            }
            
            if (clientes.length > 0) {
                clientes.forEach(cliente => {
                    const item = document.createElement('div');
                    item.className = 'suggestion-item';
                    item.innerHTML = `${cliente.nombre} | ${cliente.telefono || 'Sin teléfono'}`;
                    item.onclick = () => seleccionarCliente(cliente);
                    suggestions.appendChild(item);
                });
                suggestions.style.display = 'block';
            } else {
                suggestions.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error al buscar clientes:', error);
        });
}

function seleccionarCliente(cliente) {
    clienteSeleccionado = cliente;
    document.getElementById('clienteNombre').textContent = cliente.nombre;
    document.getElementById('clienteTelefono').textContent = cliente.telefono || 'Sin teléfono';
    document.getElementById('clienteInfo').style.display = 'block';
    document.getElementById('searchCliente').value = '';
    document.getElementById('clienteSuggestions').style.display = 'none';
}

function quitarCliente() {
    clienteSeleccionado = null;
    document.getElementById('clienteInfo').style.display = 'none';
    document.getElementById('searchCliente').value = '';
}

function agregarAlCarrito(producto) {
    const existente = carrito.find(item => item.id_producto === producto.id_producto);
    
    if (existente) {
        if (existente.cantidad < producto.stock) {
            existente.cantidad++;
            existente.subtotal = existente.cantidad * existente.precio;
        } else {
            alert('No hay suficiente stock');
            return;
        }
    } else {
        if (producto.stock > 0) {
            carrito.push({
                id_producto: producto.id_producto,
                nombre: producto.nombre,
                precio: parseFloat(producto.precio),
                cantidad: 1,
                subtotal: parseFloat(producto.precio)
            });
        } else {
            alert('Producto sin stock');
            return;
        }
    }
    
    actualizarCarrito();
}

function actualizarCarrito() {
    const cuerpo = document.getElementById('cuerpoCarrito');
    cuerpo.innerHTML = '';
    
    totalVenta = 0;
    
    carrito.forEach((item, index) => {
        totalVenta += item.subtotal;
        
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${item.nombre}</td>
            <td>$${item.precio.toFixed(2)}</td>
            <td>
                <input type="number" value="${item.cantidad}" min="1" 
                       onchange="actualizarCantidad(${index}, this.value)" style="width: 60px;">
            </td>
            <td>$${item.subtotal.toFixed(2)}</td>
            <td>
                <button class="btn btn-danger" onclick="eliminarDelCarrito(${index})">❌</button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
    
    document.getElementById('totalVenta').textContent = totalVenta.toFixed(2);
    calcularCambio();
}

function actualizarCantidad(index, nuevaCantidad) {
    nuevaCantidad = parseInt(nuevaCantidad);
    if (nuevaCantidad > 0) {
        carrito[index].cantidad = nuevaCantidad;
        carrito[index].subtotal = carrito[index].cantidad * carrito[index].precio;
        actualizarCarrito();
    }
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
}

function calcularCambio() {
    const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
    const cambio = efectivo - totalVenta;
    document.getElementById('cambio').value = cambio >= 0 ? cambio.toFixed(2) : '0.00';
}

function procesarVenta() {
    if (carrito.length === 0) {
        alert('El carrito está vacío');
        return;
    }
    
    const efectivo = parseFloat(document.getElementById('efectivo').value) || 0;
    const cambio = parseFloat(document.getElementById('cambio').value) || 0;
    const tipoVenta = document.getElementById('tipoVenta').value;
    
    if (efectivo < totalVenta && tipoVenta === 'CONTADO') {
        alert('El efectivo es menor al total de la venta');
        return;
    }
    
    const data = {
        action: 'procesarVenta',
        carrito: carrito,
        total: totalVenta,
        efectivo: efectivo,
        cambio: cambio,
        tipoVenta: tipoVenta,
        idCliente: clienteSeleccionado ? clienteSeleccionado.id_cliente : null,
        idUsuario: ID_USUARIO_SESION  
    };
    
    console.log('Enviando venta con usuario ID:', ID_USUARIO_SESION);
    
    fetch('pventaf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`✅ Venta procesada correctamente\nFolio: ${result.folio}\nID Venta: ${result.id_venta}`);
            carrito = [];
            clienteSeleccionado = null;
            actualizarCarrito();
            document.getElementById('efectivo').value = '';
            document.getElementById('clienteInfo').style.display = 'none';
            document.getElementById('searchCliente').value = '';
            cargarProductos();
        } else {
            alert('❌ Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('❌ Error al procesar la venta: ' + error);
    });
}
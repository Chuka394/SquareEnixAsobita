// principal.js — Square Enix Store

// Cerrar dropdowns y paneles al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.subnav-dropdown'))
        document.querySelectorAll('.subnav-menu').forEach(d => d.classList.remove('show'));
    if (!e.target.closest('.panel-flotante') && !e.target.closest('.nav-icon-wrap') && !e.target.closest('.subnav-cart'))
        document.querySelectorAll('.panel-flotante').forEach(p => p.classList.remove('show'));
});

function toggleDropdown(id) {
    const menu = document.getElementById(id);
    if (!menu) return;
    const abierto = menu.classList.contains('show');
    document.querySelectorAll('.subnav-menu').forEach(d => d.classList.remove('show'));
    if (!abierto) menu.classList.add('show');
}

function togglePanel(id) {
    const panel = document.getElementById(id);
    if (!panel) return;
    const abierto = panel.classList.contains('show');
    document.querySelectorAll('.panel-flotante').forEach(p => p.classList.remove('show'));
    if (!abierto) {
        panel.classList.add('show');
        const badge = document.querySelector('.nav-badge[data-panel="' + id + '"]');
        if (badge) badge.style.display = 'none';
        if (id === 'panelNotif') {
            const fd = new FormData(); fd.append('accion', 'marcar_todas');
            fetch('/squareenix/controladores/notificaciones.php', {method:'POST', body:fd}).catch(()=>{});
        }
    }
}

// Comprar todo el carrito 
function comprarCarritoCompleto(total) {
    if (!total || total <= 0) { alert('Tu carrito está vacío'); return; }
    window.location.href = '/squareenix/carrito_checkout.php';
}

// Carrusel
let idxCarrusel = 0, totalDiap = 0, timerCar = null;

function initCarrusel() {
    const pista = document.getElementById('carousel-track');
    if (!pista) return;
    totalDiap = pista.children.length;
    if (totalDiap > 1) timerCar = setInterval(() => moverCarrusel(1), 6000);
}

function moverCarrusel(dir) {
    idxCarrusel = (idxCarrusel + dir + totalDiap) % totalDiap;
    document.getElementById('carousel-track').style.transform = `translateX(-${idxCarrusel * 100}%)`;
    clearInterval(timerCar);
    timerCar = setInterval(() => moverCarrusel(1), 6000);
}

// Galeria del vieojuoge
function initGaleria() {
    const imgPrincipal = document.getElementById('main-img');
    const miniaturas   = document.querySelectorAll('.juego-thumbs img');
    if (!imgPrincipal || miniaturas.length < 2) return;
    let idxGal = 0;
    setInterval(() => {
        idxGal = (idxGal + 1) % miniaturas.length;
        imgPrincipal.src = miniaturas[idxGal].src;
        miniaturas.forEach((t, i) => t.classList.toggle('active', i === idxGal));
    }, 4000);
}

// Estrellas
function initEstrellas() {
    const estrellas  = document.querySelectorAll('.star-btn');
    const inputCal   = document.getElementById('cal-input');
    if (!estrellas.length || !inputCal) return;
    estrellas.forEach((btn, i) => {
        btn.addEventListener('click', () => {
            inputCal.value = i + 1;
            estrellas.forEach((s, j) => s.classList.toggle('active', j <= i));
        });
        btn.addEventListener('mouseover', () => estrellas.forEach((s, j) => s.classList.toggle('active', j <= i)));
        btn.addEventListener('mouseout',  () => {
            const val = parseInt(inputCal.value) || 0;
            estrellas.forEach((s, j) => s.classList.toggle('active', j < val));
        });
    });
}

// Compra
function abrirModalCompra()  { document.getElementById('modal-compra').classList.add('show'); }
function cerrarModalCompra() { document.getElementById('modal-compra').classList.remove('show'); }
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show'));
});

// Notificaciones
function marcarTodasVistas(e) {
    if (e) e.preventDefault();
    const fd = new FormData();
    fd.append('accion', 'marcar_todas');
    fetch('/squareenix/controladores/notificaciones.php', { method: 'POST', body: fd })
        .then(() => document.querySelectorAll('.nav-badge').forEach(b => b.style.display = 'none'));
}

// Biblioteca
function filtrarBib(val) {
    document.querySelectorAll('#lstJuegosBib .bib-game-item').forEach(item => {
        item.style.display = item.dataset.titulo.includes(val.toLowerCase()) ? '' : 'none';
    });
}

function toggleFormCompartir() {
    const frm = document.getElementById('formCompartir');
    if (frm) frm.style.display = frm.style.display === 'none' ? 'block' : 'none';
}

// Inicio
document.addEventListener('DOMContentLoaded', () => {
    initCarrusel();
    initGaleria();
    initEstrellas();
});

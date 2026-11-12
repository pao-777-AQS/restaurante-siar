<template>
  <q-layout view="lHh Lpr lFf" class="cliente-layout">
    <q-header class="cliente-header">
      <q-toolbar class="q-px-md q-px-lg-xl">
        <q-avatar color="white" text-color="deep-orange-8" icon="restaurant" />
        <q-toolbar-title class="q-ml-sm"><strong>SIAR</strong><span class="text-weight-regular q-ml-sm gt-xs">| Cocina con cariño</span></q-toolbar-title>
        <q-btn flat round icon="receipt_long" aria-label="Ver pedido" @click="abrirSeguimiento">
          <q-badge v-if="codigoPedido" color="green" floating>1</q-badge>
          <q-tooltip>Seguir mi pedido</q-tooltip>
        </q-btn>
        <q-btn unelevated color="white" text-color="deep-orange-9" icon="shopping_bag" :label="`Mi pedido · ${cantidadCarrito}`" class="q-ml-sm" @click="carritoAbierto = true" />
      </q-toolbar>
    </q-header>

    <q-page-container>
      <q-page>
        <section class="hero q-px-md q-px-lg-xl">
          <div class="hero-copy">
            <div class="eyebrow"><q-icon name="auto_awesome" /> SABOR QUE SE SIENTE</div>
            <h1>Algo rico<br /><span>te está esperando.</span></h1>
            <p>Explora nuestra carta, personaliza tu plato y disfruta. Tu pedido, a tu manera.</p>
            <q-btn unelevated rounded color="white" text-color="deep-orange-9" label="Ver la carta" icon-right="south" @click="document.getElementById('carta')?.scrollIntoView({ behavior: 'smooth' })" />
          </div>
          <div class="hero-art"><div class="plate"><q-icon name="lunch_dining" /></div><div class="hero-sticker"><q-icon name="favorite" /> Hecho al momento</div></div>
          <div class="hero-orb orb-one"></div><div class="hero-orb orb-two"></div>
        </section>

        <section v-if="recomendaciones.length" class="recommendations q-px-md q-px-lg-xl">
          <div class="recommendations-head"><div><div class="eyebrow text-deep-orange-8">RECOMENDADO POR NUESTROS CLIENTES</div><h2 class="section-title">Palabras que nos alegran</h2></div><q-icon name="forum" size="34px" color="deep-orange-8" /></div>
          <div class="recommendation-grid"><q-card v-for="(r, i) in recomendaciones" :key="i" flat class="recommendation-card"><q-icon name="format_quote" color="deep-orange-4" size="25px"/><div>{{ r.mensaje }}</div><div class="text-caption text-grey-7 q-mt-sm">Cliente verificado</div></q-card></div>
        </section>

        <main id="carta" class="catalog q-px-md q-px-lg-xl">
          <div class="row items-end justify-between q-mb-lg">
            <div><div class="eyebrow text-deep-orange-8">DESCUBRE</div><h2 class="section-title">Nuestra carta</h2><p class="text-grey-7 q-mb-none">Preparado fresco para ti.</p></div>
            <q-input v-model="busqueda" outlined dense rounded placeholder="Buscar un plato..." class="search-input"><template #prepend><q-icon name="search" /></template></q-input>
          </div>
          <div class="row q-gutter-sm q-mb-xl">
            <q-btn v-for="cat in categorias" :key="cat" rounded no-caps :outline="categoriaActiva !== cat" :color="categoriaActiva === cat ? 'deep-orange-8' : 'grey-7'" :label="cat" @click="categoriaActiva = cat" />
          </div>
          <div v-if="cargando" class="row justify-center q-pa-xl"><q-spinner-dots color="deep-orange" size="48px" /></div>
          <div v-else-if="errorMenu" class="empty-state"><q-icon name="restaurant_menu" size="42px" /><div>{{ errorMenu }}</div><q-btn flat color="deep-orange" label="Volver a intentar" @click="cargarMenu" /></div>
          <div v-else-if="productosFiltrados.length" class="product-grid">
            <q-card v-for="producto in productosFiltrados" :key="producto.id_producto" flat bordered class="product-card">
              <div class="product-photo" :style="producto.imagen_url ? { backgroundImage: `url(${producto.imagen_url})` } : {}">
                <q-icon v-if="!producto.imagen_url" name="ramen_dining" />
                <q-badge v-if="producto.descuento_porcentaje" color="green-7" class="discount-badge">-{{ producto.descuento_porcentaje }}% hoy</q-badge>
              </div>
              <q-card-section class="q-pb-sm"><div class="text-caption text-deep-orange-8 text-weight-bold">{{ producto.categoria || 'Especialidad' }}</div><div class="text-h6 text-weight-bold q-mt-xs">{{ producto.nombre }}</div><div class="text-body2 text-grey-7 product-description">{{ producto.descripcion || 'Una deliciosa opción preparada en nuestra cocina.' }}</div></q-card-section>
              <q-card-actions class="q-px-md q-pb-md justify-between"><div><div v-if="producto.descuento_porcentaje" class="text-caption text-grey strike">{{ dinero(producto.precio) }}</div><strong class="price">{{ dinero(producto.precio * (1 - producto.descuento_porcentaje / 100)) }}</strong></div><q-btn round unelevated color="deep-orange-8" icon="add" @click="agregar(producto)"><q-tooltip>Agregar al pedido</q-tooltip></q-btn></q-card-actions>
            </q-card>
          </div>
          <div v-else class="empty-state"><q-icon name="search_off" size="42px" /><div>No encontramos platos con esa búsqueda.</div></div>
        </main>

        <footer class="customer-footer"><q-icon name="restaurant" /> SIAR <span>·</span> Hecho para compartir.</footer>
      </q-page>
    </q-page-container>

    <q-drawer v-model="carritoAbierto" side="right" overlay bordered :width="430" class="cart-drawer">
      <div class="cart-head"><div><div class="eyebrow text-deep-orange-8">CASI LISTO</div><div class="text-h5 text-weight-bold">Tu pedido</div></div><q-btn flat round icon="close" @click="carritoAbierto = false" /></div>
      <div v-if="!carrito.length" class="cart-empty"><q-icon name="shopping_bag" size="54px" color="grey-5" /><div class="text-h6 q-mt-md">Tu bolsa está vacía</div><div class="text-grey-7">Agrega algo rico de la carta.</div></div>
      <div v-else class="cart-content">
        <div v-for="item in carrito" :key="item.id_producto" class="cart-item">
          <div class="row items-start no-wrap"><div class="col"><strong>{{ item.nombre }}</strong><div class="text-caption text-grey-7">{{ dinero(item.precio) }} c/u</div></div><q-btn flat round dense icon="delete_outline" color="grey-7" @click="quitar(item.id_producto)" /></div>
          <q-select v-if="item.adiciones_disponibles?.length" v-model="item.adiciones" :options="item.adiciones_disponibles" option-label="nombre" option-value="id_adicion" emit-value map-options multiple use-chips outlined dense label="Personaliza tu plato" class="q-mt-sm" />
          <div class="row items-center justify-between q-mt-sm"><div class="quantity"><q-btn flat round dense icon="remove" @click="cambiarCantidad(item, -1)" /><strong>{{ item.cantidad }}</strong><q-btn flat round dense icon="add" @click="cambiarCantidad(item, 1)" /></div><strong>{{ dinero(subtotalItem(item)) }}</strong></div>
        </div>
        <q-separator class="q-my-md" />
        <div class="row justify-between text-body1"><span>Subtotal estimado</span><strong>{{ dinero(subtotal) }}</strong></div>
        <div v-if="descuento > 0" class="row justify-between text-positive q-mt-xs"><span>Descuentos de hoy</span><strong>-{{ dinero(descuento) }}</strong></div>
        <q-btn class="full-width q-mt-lg" unelevated rounded color="deep-orange-8" label="Continuar al checkout" icon-right="arrow_forward" @click="dialogCheckout = true" />
      </div>
    </q-drawer>

    <q-dialog v-model="dialogCheckout" persistent>
      <q-card class="checkout-card"><q-card-section class="row items-center justify-between"><div><div class="eyebrow text-deep-orange-8">ÚLTIMO PASO</div><div class="text-h5 text-weight-bold">¿Cómo lo prefieres?</div></div><q-btn flat round icon="close" v-close-popup /></q-card-section>
        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-sm q-mb-md"><div class="col"><q-btn class="full-width order-type" :outline="tipoPedido !== 'mesa'" color="deep-orange-8" icon="table_restaurant" label="En mesa" :disable="!codigoQr" @click="tipoPedido = 'mesa'" /></div><div class="col"><q-btn class="full-width order-type" :outline="tipoPedido !== 'recoger'" color="deep-orange-8" icon="takeout_dining" label="Para recoger" @click="tipoPedido = 'recoger'" /></div></div>
          <q-banner v-if="codigoQr && tipoPedido === 'mesa'" rounded class="bg-green-1 text-green-10 q-mb-md"><q-icon name="check_circle" /> Mesa detectada por QR</q-banner>
          <q-banner v-else-if="!codigoQr" rounded class="bg-orange-1 text-orange-10 q-mb-md">Para pedir en mesa, escanea el QR que está sobre tu mesa. También puedes pedir para recoger.</q-banner>
          <q-input v-model="clienteNombre" outlined label="Tu nombre" class="q-mb-sm" maxlength="160" />
          <q-input v-model="clienteTelefono" outlined label="Teléfono de contacto" class="q-mb-sm" maxlength="40" />
          <q-input v-model.number="propina" outlined type="number" min="0" label="Propina (opcional)" prefix="$" hint="Se suma al total del pedido." />
          <q-banner class="bg-grey-2 q-mt-md" rounded><q-icon name="payments" class="q-mr-sm" />Pago al recibir en el restaurante. El pago con tarjeta se habilitará más adelante.</q-banner>
          <div class="row justify-between text-h6 q-mt-md"><span>Total</span><strong>{{ dinero(subtotal - descuento + Number(propina || 0)) }}</strong></div>
          <q-banner v-if="errorCheckout" class="bg-red-1 text-negative q-mt-sm" rounded>{{ errorCheckout }}</q-banner>
          <q-btn class="full-width q-mt-md" unelevated rounded color="deep-orange-8" :loading="enviando" label="Confirmar pedido" icon="check" @click="confirmarPedido" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogSeguimiento">
      <q-card class="track-card"><q-card-section class="row justify-between items-center"><div><div class="eyebrow text-deep-orange-8">EN CAMINO</div><div class="text-h5 text-weight-bold">Tu pedido</div></div><q-btn flat round icon="close" v-close-popup /></q-card-section>
        <q-card-section v-if="pedido">
          <q-banner rounded class="bg-orange-1 text-deep-orange-10"><q-icon name="schedule" class="q-mr-sm" /><strong>{{ estadoBonito(pedido.estado) }}</strong> · {{ tiempoRestante }}</q-banner>
          <div v-if="pedido.mesero" class="text-body2 q-mt-md"><q-icon name="person" /> Te atiende {{ pedido.mesero }}</div>
          <q-list separator class="q-mt-md"><q-item v-for="(item, i) in pedido.items" :key="i"><q-item-section>{{ item.cantidad }} × {{ item.nombre }}<q-item-label caption>{{ item.adiciones.map((a) => a.nombre).join(', ') }}</q-item-label></q-item-section><q-item-section side>{{ dinero(item.subtotal) }}</q-item-section></q-item></q-list>
          <div class="row justify-between q-mt-md text-h6"><span>Total</span><strong>{{ dinero(pedido.total) }}</strong></div>
          <q-separator class="q-my-md" />
          <div class="row items-center justify-between"><strong>Mensajes privados</strong><span class="text-caption text-grey-7">Solo tú y el restaurante</span></div>
          <div class="message-list"><div v-for="(m, i) in pedido.mensajes.filter((x) => x.visibilidad === 'privado')" :key="i" class="message-bubble" :class="m.autor === 'cliente' ? 'mine' : ''">{{ m.mensaje }}</div></div>
          <div class="row q-gutter-xs q-mt-sm"><q-input v-model="mensajePrivado" outlined dense class="col" placeholder="Escribe al restaurante..." @keyup.enter="enviarMensaje('privado')" /><q-btn round unelevated color="deep-orange-8" icon="send" :disable="!mensajePrivado.trim()" @click="enviarMensaje('privado')" /></div>
          <q-separator class="q-my-md" />
          <div class="row items-center justify-between"><strong>Recomendación pública</strong><q-badge color="orange-2" text-color="orange-10">La revisará el restaurante</q-badge></div>
          <div v-for="(m, i) in pedido.recomendaciones_publicas" :key="`p${i}`" class="public-comment"><q-icon name="recommend" /> Cliente verificado: {{ m.mensaje }}</div>
          <div class="row q-gutter-xs q-mt-sm"><q-input v-model="mensajePublico" outlined dense class="col" placeholder="Comparte una recomendación..." @keyup.enter="enviarMensaje('publico')" /><q-btn round unelevated color="deep-orange-8" icon="send" :disable="!mensajePublico.trim()" @click="enviarMensaje('publico')" /></div>
        </q-card-section>
        <q-card-section v-else class="text-center"><q-spinner-dots color="deep-orange" size="40px" /></q-card-section>
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Notify } from 'quasar'
import { api } from '@/services/api'

const route = useRoute()
const productos = ref([]); const recomendaciones = ref([]); const cargando = ref(true); const errorMenu = ref(''); const busqueda = ref(''); const categoriaActiva = ref('Todo')
const carrito = ref([]); const carritoAbierto = ref(false); const dialogCheckout = ref(false); const dialogSeguimiento = ref(false)
const clienteNombre = ref(''); const clienteTelefono = ref(''); const propina = ref(0); const tipoPedido = ref(route.query.qr ? 'mesa' : 'recoger')
const codigoQr = String(route.query.qr || ''); const enviando = ref(false); const errorCheckout = ref(''); const codigoPedido = ref(localStorage.getItem('siar_codigo_pedido') || '')
const pedido = ref(null); const mensajePrivado = ref(''); const mensajePublico = ref(''); const ahora = ref(Date.now()); let poll

const categorias = computed(() => ['Todo', ...new Set(productos.value.map((p) => p.categoria).filter(Boolean))])
const productosFiltrados = computed(() => productos.value.filter((p) => (categoriaActiva.value === 'Todo' || p.categoria === categoriaActiva.value) && `${p.nombre} ${p.descripcion || ''}`.toLowerCase().includes(busqueda.value.toLowerCase())))
const cantidadCarrito = computed(() => carrito.value.reduce((s, i) => s + i.cantidad, 0))
const subtotalItem = (item) => item.cantidad * (item.precio + (item.adiciones_disponibles || []).filter((a) => item.adiciones.includes(a.id_adicion)).reduce((s, a) => s + a.precio, 0)) * (1 - item.descuento_porcentaje / 100)
const subtotal = computed(() => carrito.value.reduce((s, i) => s + i.cantidad * i.precio, 0) + carrito.value.reduce((s, i) => s + i.cantidad * (i.adiciones_disponibles || []).filter((a) => i.adiciones.includes(a.id_adicion)).reduce((x, a) => x + a.precio, 0), 0))
const descuento = computed(() => carrito.value.reduce((s, i) => s + i.cantidad * (i.precio + (i.adiciones_disponibles || []).filter((a) => i.adiciones.includes(a.id_adicion)).reduce((x, a) => x + a.precio, 0)) * i.descuento_porcentaje / 100, 0))
const tiempoRestante = computed(() => { ahora.value; if (!pedido.value?.estimado_listo_en) return 'El restaurante está preparando tu pedido'; const ms = new Date(pedido.value.estimado_listo_en).getTime() - Date.now(); return ms <= 0 ? '¡Ya casi está listo!' : `Tiempo estimado: ${Math.ceil(ms / 60000)} min` })
function dinero(n) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(n || 0)) }
function estadoBonito(e) { return ({ pendiente: 'Recibimos tu pedido', preparando: 'En preparación', en_preparacion: 'En preparación', listo: 'Listo para entregar', entregado: 'Pedido entregado', cancelado: 'Pedido cancelado' })[e] || e }
async function cargarMenu() { cargando.value = true; errorMenu.value = ''; try { const { data } = await api.get('/cliente/menu'); productos.value = data.data || []; recomendaciones.value = data.recomendaciones || [] } catch { errorMenu.value = 'No pudimos cargar la carta. Revisa que el servidor esté activo e intenta de nuevo.' } finally { cargando.value = false } }
function agregar(p) { const actual = carrito.value.find((i) => i.id_producto === p.id_producto); if (actual) actual.cantidad++; else carrito.value.push({ ...p, cantidad: 1, adiciones: [], adiciones_disponibles: p.adiciones || [] }); Notify.create({ message: `${p.nombre} agregado`, color: 'positive', icon: 'check', timeout: 1000 }) }
function cambiarCantidad(i, d) { i.cantidad += d; if (i.cantidad <= 0) quitar(i.id_producto) }
function quitar(id) { carrito.value = carrito.value.filter((i) => i.id_producto !== id) }
async function confirmarPedido() {
  errorCheckout.value = ''; if (!clienteNombre.value.trim() || !clienteTelefono.value.trim()) { errorCheckout.value = 'Escribe tu nombre y teléfono para continuar.'; return }
  if (tipoPedido.value === 'mesa' && !codigoQr) { errorCheckout.value = 'Escanea el QR de tu mesa o selecciona para recoger.'; return }
  enviando.value = true
  try {
    const { data } = await api.post('/cliente/pedidos', { tipo_pedido: tipoPedido.value, ...(tipoPedido.value === 'mesa' ? { codigo_qr: codigoQr } : {}), cliente_nombre: clienteNombre.value.trim(), cliente_telefono: clienteTelefono.value.trim(), propina: Number(propina.value || 0), items: carrito.value.map((i) => ({ id_producto: i.id_producto, cantidad: i.cantidad, adiciones: i.adiciones, nota: '' })) })
    pedido.value = data.data; codigoPedido.value = data.data.codigo_seguimiento; localStorage.setItem('siar_codigo_pedido', codigoPedido.value); carrito.value = []; dialogCheckout.value = false; carritoAbierto.value = false; dialogSeguimiento.value = true; Notify.create({ type: 'positive', message: '¡Pedido enviado a cocina!' }); iniciarSeguimiento()
  } catch (e) { errorCheckout.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'No se pudo enviar. Revisa la conexión con el restaurante.' }
  finally { enviando.value = false }
}
async function actualizarPedido() { if (!codigoPedido.value) return; try { const { data } = await api.get(`/cliente/pedidos/${codigoPedido.value}`); pedido.value = data.data } catch { /* Se conserva el estado visible si se pierde la conexión momentáneamente. */ } }
function iniciarSeguimiento() { clearInterval(poll); actualizarPedido(); poll = setInterval(() => { ahora.value = Date.now(); actualizarPedido() }, 15000) }
function abrirSeguimiento() { if (!codigoPedido.value) { Notify.create('Aún no tienes pedidos activos.'); return } dialogSeguimiento.value = true; iniciarSeguimiento() }
async function enviarMensaje(visibilidad) { const ref = visibilidad === 'privado' ? mensajePrivado : mensajePublico; const texto = ref.value.trim(); if (!texto || !codigoPedido.value) return; try { await api.post(`/cliente/pedidos/${codigoPedido.value}/mensajes`, { mensaje: texto, visibilidad }); ref.value = ''; await actualizarPedido(); Notify.create({ message: visibilidad === 'publico' ? 'Recomendación enviada para revisión.' : 'Mensaje enviado al restaurante.', color: 'positive' }) } catch { Notify.create({ message: 'No se pudo enviar el mensaje.', color: 'negative' }) } }
onMounted(() => { cargarMenu(); if (codigoPedido.value) iniciarSeguimiento() })
onUnmounted(() => clearInterval(poll))
</script>

<style scoped>
.cliente-layout{background:#faf8f5;color:#26231f}.cliente-header{background:linear-gradient(100deg,#b93818,#e15b2d);color:white}.hero{position:relative;overflow:hidden;min-height:400px;background:linear-gradient(115deg,#a53216,#dc5428 67%,#ed7443);color:white;display:flex;align-items:center;padding-top:46px;padding-bottom:46px}.hero-copy{position:relative;z-index:2;max-width:580px}.eyebrow{font-size:11px;letter-spacing:1.7px;font-weight:800;display:flex;align-items:center;gap:6px}.hero h1{font-size:clamp(42px,6vw,68px);line-height:1.03;letter-spacing:-2px;margin:20px 0 16px;font-weight:850}.hero h1 span{color:#ffd4ae}.hero p{max-width:430px;font-size:17px;line-height:1.6;color:#ffe8dc;margin-bottom:24px}.hero-art{position:absolute;right:12%;top:50%;transform:translateY(-50%);width:280px;height:280px;border-radius:50%;background:#ffbe7e;display:grid;place-items:center;box-shadow:0 22px 50px #68220e55}.plate{width:205px;height:205px;border-radius:50%;background:#fff9ed;display:grid;place-items:center;box-shadow:inset 0 0 0 12px #f7e7d0}.plate .q-icon{font-size:115px;color:#c84921}.hero-sticker{position:absolute;right:-50px;bottom:25px;background:white;color:#9e341b;padding:12px 17px;border-radius:30px;font-weight:700;box-shadow:0 8px 25px #64261033}.orb-one,.orb-two{position:absolute;border:1px solid #ffffff23;border-radius:50%;width:370px;height:370px;right:-100px;top:-230px}.orb-two{width:540px;height:540px;right:-180px;top:auto;bottom:-390px}.catalog{max-width:1320px;margin:0 auto;padding-top:54px;padding-bottom:48px}.section-title{font-size:34px;margin:5px 0 0;font-weight:800;letter-spacing:-1px}.search-input{width:270px}.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(235px,1fr));gap:20px}.product-card{border-radius:18px;background:white;overflow:hidden;transition:transform .18s,box-shadow .18s}.product-card:hover{transform:translateY(-4px);box-shadow:0 14px 36px #59321e16}.product-photo{height:170px;background:linear-gradient(135deg,#fae6d5,#f3c69f);background-position:center;background-size:cover;display:grid;place-items:center;position:relative}.product-photo>.q-icon{font-size:74px;color:#c3582d}.discount-badge{position:absolute;top:12px;left:12px}.product-description{min-height:44px;line-height:1.45;margin-top:6px}.price{font-size:18px;color:#ad3c1b}.strike{text-decoration:line-through}.customer-footer{padding:24px;text-align:center;color:#82786f;background:#f1ede8;font-weight:700}.customer-footer span{margin:0 7px;color:#d45b32}.empty-state{padding:65px 16px;text-align:center;color:#7b716a;display:grid;justify-items:center;gap:12px}.cart-head{padding:24px 22px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eee}.cart-content{padding:20px 22px}.cart-empty{height:65vh;display:flex;flex-direction:column;align-items:center;justify-content:center}.cart-item{padding:15px 0;border-bottom:1px solid #eee}.quantity{border:1px solid #eee;border-radius:30px;display:flex;align-items:center}.checkout-card,.track-card{width:min(530px,94vw);border-radius:18px}.order-type{height:55px}.message-list{display:flex;flex-direction:column;gap:8px;margin-top:10px;max-height:160px;overflow:auto}.message-bubble{background:#f1eee9;padding:9px 12px;border-radius:12px;max-width:85%;font-size:14px}.message-bubble.mine{align-self:flex-end;background:#ffede4;color:#8f321a}.public-comment{margin-top:9px;padding:9px 11px;background:#f4f0e9;border-radius:10px;color:#635b53;font-size:13px}.public-comment .q-icon{color:#d05a2b}.track-card{max-height:90vh;overflow:auto}@media(max-width:800px){.hero{min-height:360px}.hero-art{opacity:.2;right:-85px}.hero-copy{max-width:100%}.search-input{width:100%;margin-top:18px}.catalog>.row.items-end{align-items:flex-start}.section-title{font-size:29px}}@media(max-width:480px){.hero h1{font-size:44px}.product-grid{grid-template-columns:1fr 1fr;gap:12px}.product-photo{height:125px}.product-card .text-h6{font-size:16px}.product-description{font-size:12px}.cart-drawer{width:100%!important}}
.recommendations{max-width:1320px;margin:0 auto;padding-top:38px}.recommendations-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.recommendation-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}.recommendation-card{padding:17px;border:1px solid #eee5dc;border-radius:14px;color:#55483f;line-height:1.5;background:#fff}
</style>

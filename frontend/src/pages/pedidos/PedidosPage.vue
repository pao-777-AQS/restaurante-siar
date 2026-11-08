<template>
  <q-page padding>
    <div class="row items-center justify-between q-mb-lg"><div><h1 class="page-titulo">Pedidos</h1><p class="page-subtitulo">Pedidos de clientes en tiempo real, con seguimiento y comentarios.</p></div><q-btn outline color="primary" icon="refresh" label="Actualizar" @click="cargar" /></div>
    <q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" rounded>{{ error }}</q-banner>
    <div v-if="cargando" class="row justify-center q-pa-xl"><q-spinner-dots color="primary" size="42px" /></div>
    <div v-else-if="!pedidos.length" class="text-center q-pa-xl bg-white rounded-borders"><q-icon name="receipt_long" size="56px" color="grey-5"/><div class="text-h6 q-mt-md">Todavía no hay pedidos activos</div><div class="text-grey-7">Los pedidos nuevos aparecerán aquí.</div></div>
    <div v-else class="orders-grid">
      <q-card v-for="p in pedidos" :key="p.id_pedido" flat bordered class="order-card">
        <q-card-section class="row items-start justify-between q-pb-sm"><div><div class="row items-center q-gutter-sm"><q-badge :color="colorEstado(p.estado)" :label="estadoLabel(p.estado)"/><q-badge outline color="grey-7" :label="p.tipo_pedido === 'mesa' ? `Mesa ${p.mesa || ''}` : 'Para recoger'"/></div><div class="text-h6 text-weight-bold q-mt-sm">{{ p.cliente_nombre }}</div><div class="text-caption text-grey-7">{{ p.cliente_telefono }} · {{ fecha(p.estimado_listo_en) }}</div></div><div class="text-right"><div class="text-h6 text-weight-bold">{{ dinero(p.total) }}</div><div class="text-caption text-grey-7">Propina {{ dinero(p.propina) }}</div></div></q-card-section>
        <q-separator />
        <q-card-section class="q-py-sm"><div v-for="(item, i) in p.items" :key="i" class="row justify-between text-body2 q-py-xs"><span><strong>{{ item.cantidad }}×</strong> {{ item.nombre }}<span v-if="item.adiciones.length" class="text-grey-7"> · +{{ item.adiciones.join(', ') }}</span><div v-if="item.nota" class="text-caption text-orange-9">Nota: {{ item.nota }}</div></span><span>{{ dinero(item.subtotal) }}</span></div></q-card-section>
        <q-card-section class="bg-grey-1 q-py-sm">
          <div class="row q-col-gutter-sm items-center"><div class="col-12 col-sm"><q-select v-model="p.estado" dense outlined :options="estados" emit-value map-options label="Estado del pedido" @update:model-value="guardarEstado(p)" /></div><div class="col-6 col-sm-3"><q-input v-model.number="p.tiempo_estimado_minutos" dense outlined type="number" min="1" max="240" label="Minutos" /></div><div class="col-6 col-sm-auto"><q-btn dense unelevated color="primary" label="Guardar" @click="guardarEstado(p)" /></div></div>
          <div class="row items-center justify-between q-mt-sm"><div class="text-body2"><q-icon name="person" /> Mesero: <strong>{{ p.mesero?.nombres || 'Sin asignar' }}</strong></div><q-btn v-if="!p.mesero && auth.tienePermiso('tomar_pedidos')" flat dense color="primary" label="Asignarme" icon="person_add" @click="asignar(p)" /></div>
        </q-card-section>
        <q-expansion-item icon="forum" label="Comentarios y recomendaciones" :caption="`${p.mensajes.length} mensaje(s)`" header-class="text-weight-medium">
          <q-card-section class="q-pt-none">
            <div v-if="!p.mensajes.length" class="text-grey-7 text-caption">Sin mensajes todavía.</div>
            <div v-for="m in p.mensajes" :key="m.id_mensaje" class="staff-message q-mb-sm">
              <div class="row justify-between items-center"><strong>{{ m.autor === 'cliente' ? 'Cliente' : 'Restaurante' }} · {{ m.visibilidad === 'publico' ? 'Público' : 'Privado' }}</strong><q-badge v-if="m.visibilidad === 'publico'" :color="m.estado_publicacion === 'publicado' ? 'green' : m.estado_publicacion === 'oculto' ? 'grey' : 'orange'" :label="m.estado_publicacion" /></div>
              <div class="q-mt-xs">{{ m.mensaje }}</div>
              <div v-if="m.visibilidad === 'publico' && m.estado_publicacion === 'pendiente'" class="row q-gutter-xs q-mt-xs"><q-btn dense flat color="positive" label="Publicar" icon="check" @click="moderar(m, 'publicado')"/><q-btn dense flat color="negative" label="Ocultar" icon="visibility_off" @click="moderar(m, 'oculto')"/></div>
            </div>
            <div class="row q-gutter-xs q-mt-md"><q-input v-model="respuestas[p.id_pedido]" dense outlined class="col" placeholder="Responder en privado..." @keyup.enter="responder(p)"/><q-btn round dense unelevated color="primary" icon="send" :disable="!respuestas[p.id_pedido]?.trim()" @click="responder(p)"/></div>
          </q-card-section>
        </q-expansion-item>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Notify } from 'quasar'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
const auth = useAuthStore()
const pedidos = ref([]); const cargando = ref(true); const error = ref(''); const respuestas = ref({}); let reloj
const estados = [{ label: 'Pendiente', value: 'pendiente' }, { label: 'En preparación', value: 'preparando' }, { label: 'Listo', value: 'listo' }, { label: 'Entregado', value: 'entregado' }, { label: 'Cancelado', value: 'cancelado' }]
function dinero(n) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(Number(n || 0)) }
function fecha(f) { return f ? new Date(f).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : 'Hora por definir' }
function estadoLabel(e) { return estados.find((x) => x.value === e)?.label || e }
function colorEstado(e) { return ({ pendiente: 'orange', preparando: 'blue', listo: 'green', entregado: 'grey', cancelado: 'red' })[e] || 'grey' }
async function cargar() { try { error.value = ''; const { data } = await api.get('/pedidos'); pedidos.value = data.data.map((p) => ({ ...p, tiempo_estimado_minutos: p.tiempo_estimado_minutos || 30 })) } catch (e) { error.value = e.response?.data?.message || 'No pudimos cargar los pedidos. Verifica que tu usuario tenga permiso para gestionar pedidos.' } finally { cargando.value = false } }
async function guardarEstado(p) { try { await api.patch(`/pedidos/${p.id_pedido}/estado`, { estado: p.estado, tiempo_estimado_minutos: Number(p.tiempo_estimado_minutos) || 30 }); Notify.create({ type: 'positive', message: 'Pedido actualizado.' }); cargar() } catch { Notify.create({ type: 'negative', message: 'No se pudo actualizar el pedido.' }) } }
async function asignar(p) { try { await api.patch(`/pedidos/${p.id_pedido}/mesero`); Notify.create({ type: 'positive', message: 'Pedido asignado a ti.' }); cargar() } catch (e) { Notify.create({ type: 'negative', message: e.response?.data?.message || 'No se pudo asignar el pedido.' }) } }
async function moderar(m, estado_publicacion) { try { await api.patch(`/pedidos/mensajes/${m.id_mensaje}/moderacion`, { estado_publicacion }); cargar() } catch { Notify.create({ type: 'negative', message: 'No se pudo moderar la recomendación.' }) } }
async function responder(p) { const mensaje = respuestas.value[p.id_pedido]?.trim(); if (!mensaje) return; try { await api.post(`/pedidos/${p.id_pedido}/mensajes`, { mensaje }); respuestas.value[p.id_pedido] = ''; await cargar() } catch { Notify.create({ type: 'negative', message: 'No se pudo enviar la respuesta.' }) } }
onMounted(() => { cargar(); reloj = setInterval(cargar, 15000) }); onUnmounted(() => clearInterval(reloj))
</script>

<style scoped>
.orders-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,440px),1fr));gap:16px}.order-card{border-radius:12px;overflow:hidden}.staff-message{padding:10px 12px;border-radius:10px;background:#f7f6f4;font-size:13px}
</style>

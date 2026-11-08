<template>
  <q-page padding>
    <div class="row items-center q-mb-md q-col-gutter-sm">
      <div class="col-12 col-sm">
        <h1 class="page-titulo">Mesas</h1>
        <p class="page-subtitulo">Estado de las mesas en tiempo real. Se actualiza cada 30 segundos.</p>
      </div>
      <div class="col-12 col-sm-auto row q-gutter-sm">
        <q-btn v-if="puedeReservar" outline color="primary" icon="event" label="Nueva reserva" no-caps @click="abrirReserva(null)" />
        <q-btn v-if="puedeGestionar" color="primary" icon="add" label="Nueva mesa" unelevated no-caps @click="abrirFormulario(null)" />
      </div>
    </div>

    <!-- Resumen por estado: también sirve como filtro -->
    <div class="row q-gutter-sm q-mb-md">
      <q-chip
        clickable
        :outline="filtroEstado !== null"
        color="primary"
        text-color="white"
        icon="table_restaurant"
        :label="`Todas · ${totalMesas}`"
        @click="filtroEstado = null"
      />
      <q-chip
        v-for="(info, estado) in ESTADOS_MESA"
        :key="estado"
        clickable
        :outline="filtroEstado !== estado"
        :color="info.color"
        text-color="white"
        :icon="info.icono"
        :label="`${info.etiqueta} · ${resumen[estado] ?? 0}`"
        @click="filtroEstado = filtroEstado === estado ? null : estado"
      />
    </div>

    <div v-if="cargando && mesas.length === 0" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="40px" />
    </div>

    <q-banner v-else-if="mesasVisibles.length === 0" rounded class="bg-grey-2 text-grey-8">
      {{ mesas.length === 0 ? 'Todavía no hay mesas registradas.' : 'No hay mesas en ese estado.' }}
    </q-banner>

    <div class="row q-col-gutter-md">
      <div v-for="mesa in mesasVisibles" :key="mesa.id_mesa" class="col-6 col-sm-4 col-md-3 col-lg-2">
        <q-card flat bordered class="full-height column" :class="`bg-${ESTADOS_MESA[mesa.estado].fondo}`">
          <q-card-section class="col q-pb-sm">
            <div class="row items-start no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Mesa</div>
                <div class="text-h4 text-weight-bold">{{ mesa.numero }}</div>
              </div>
              <q-btn v-if="puedeGestionar" flat round dense icon="more_vert" size="sm" aria-label="Opciones de la mesa">
                <q-menu auto-close>
                  <q-list dense style="min-width: 160px">
                    <q-item clickable @click="abrirFormulario(mesa)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section>Editar</q-item-section>
                    </q-item>
                    <q-item clickable @click="eliminar(mesa)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section>Dar de baja</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </div>
            <q-badge :color="ESTADOS_MESA[mesa.estado].color" class="q-mt-xs">
              <q-icon :name="ESTADOS_MESA[mesa.estado].icono" size="14px" class="q-mr-xs" />
              {{ mesa.estado_etiqueta }}
            </q-badge>
            <div class="text-caption text-grey-8 q-mt-sm">
              <q-icon name="person" /> {{ mesa.capacidad }} personas
              <span v-if="mesa.ubicacion"> · {{ mesa.ubicacion }}</span>
            </div>
            <div v-if="mesa.proxima_reserva" class="text-caption q-mt-xs text-warning text-weight-medium">
              <q-icon name="event" />
              {{ formatearDia(mesa.proxima_reserva.fecha) }}, {{ formatearHora(mesa.proxima_reserva.hora) }} ·
              {{ mesa.proxima_reserva.cliente_nombre }}
            </div>
          </q-card-section>

          <q-card-actions v-if="accionesDe(mesa).length" class="q-px-sm q-pt-none">
            <q-btn
              v-for="accion in accionesDe(mesa)"
              :key="accion"
              dense
              unelevated
              no-caps
              size="sm"
              :color="ACCIONES_MESA[accion].color"
              :icon="ACCIONES_MESA[accion].icono"
              :label="ACCIONES_MESA[accion].etiqueta"
              :loading="cambiando === `${mesa.id_mesa}-${accion}`"
              @click="cambiarEstado(mesa, accion)"
            />
          </q-card-actions>
          <q-card-actions v-if="puedeGestionar" class="q-px-sm q-pt-none q-pb-sm">
            <q-btn flat dense no-caps color="primary" icon="qr_code_2" label="QR del cliente" @click="mostrarQr(mesa)" />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <MesaFormDialog v-model="formularioAbierto" :mesa="mesaSeleccionada" :ubicaciones="ubicaciones" @guardado="cargar" />
    <ReservaFormDialog v-model="reservaAbierta" :id-mesa-inicial="mesaReserva" @guardado="cargar" />
    <q-dialog v-model="qrAbierto">
      <q-card class="qr-dialog">
        <q-card-section class="row items-center justify-between">
          <div><div class="text-h6 text-weight-bold">QR · Mesa {{ mesaQr?.numero }}</div><div class="text-caption text-grey-7">El cliente lo escanea para vincular su pedido a esta mesa.</div></div>
          <q-btn flat round icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="column items-center q-pt-none">
          <img v-if="imagenQr" :src="imagenQr" alt="Código QR de la mesa" class="qr-image" />
          <q-spinner v-else color="primary" size="32px" />
          <div class="text-caption text-grey-7 text-center q-mt-sm qr-url">{{ urlQr }}</div>
          <div class="row q-gutter-sm q-mt-md">
            <q-btn outline color="primary" icon="content_copy" label="Copiar enlace" @click="copiarQr" />
            <q-btn unelevated color="primary" icon="print" label="Imprimir QR" @click="imprimirQr" />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import QRCode from 'qrcode'
import { useAuthStore } from '@/stores/auth'
import mesasService from '@/services/mesasService'
import MesaFormDialog from '@/components/mesas/MesaFormDialog.vue'
import ReservaFormDialog from '@/components/mesas/ReservaFormDialog.vue'
import { mensajeError } from '@/utils/errores'
import { ACCIONES_MESA, ESTADOS_MESA, formatearDia, formatearHora } from '@/utils/mesas'

const $q = useQuasar()
const auth = useAuthStore()

const puedeGestionar = computed(() => auth.tienePermiso('gestionar_mesas'))
const puedeReservar = computed(() => auth.tienePermiso('gestionar_reservas') || puedeGestionar.value)

const mesas = ref([])
const resumen = ref({})
const cargando = ref(false)
const cambiando = ref(null)
const filtroEstado = ref(null)
const formularioAbierto = ref(false)
const mesaSeleccionada = ref(null)
const reservaAbierta = ref(false)
const mesaReserva = ref(null)
const qrAbierto = ref(false)
const mesaQr = ref(null)
const imagenQr = ref('')
const urlQr = computed(() => mesaQr.value?.codigo_qr ? `${window.location.origin}/cliente?qr=${mesaQr.value.codigo_qr}` : '')

const totalMesas = computed(() => mesas.value.length)
const mesasVisibles = computed(() =>
  filtroEstado.value ? mesas.value.filter((m) => m.estado === filtroEstado.value) : mesas.value,
)
const ubicaciones = computed(() => [...new Set(mesas.value.map((m) => m.ubicacion).filter(Boolean))].sort())

/**
 * El backend dice qué permite el estado (patrón State); aquí se filtra por
 * permisos. "reservar" no es un botón: la mesa se reserva sola al acercarse la hora.
 */
function accionesDe(mesa) {
  return mesa.acciones.filter((accion) => {
    if (accion === 'ocupar' || accion === 'liberar') return puedeReservar.value
    if (accion === 'enviarAMantenimiento' || accion === 'habilitar') return puedeGestionar.value
    return false
  })
}

async function cargar() {
  cargando.value = true
  try {
    const respuesta = await mesasService.mesas()
    mesas.value = respuesta.data
    resumen.value = respuesta.resumen
  } catch (error) {
    $q.notify({ type: 'negative', message: mensajeError(error) })
  } finally {
    cargando.value = false
  }
}

async function cambiarEstado(mesa, accion) {
  cambiando.value = `${mesa.id_mesa}-${accion}`
  try {
    const respuesta = await mesasService.cambiarEstado(mesa.id_mesa, accion)
    $q.notify({ type: 'positive', message: respuesta.message })
    await cargar()
  } catch (error) {
    $q.notify({ type: 'negative', message: mensajeError(error) })
  } finally {
    cambiando.value = null
  }
}

function abrirFormulario(mesa) {
  mesaSeleccionada.value = mesa
  formularioAbierto.value = true
}

function abrirReserva(mesa) {
  mesaReserva.value = mesa?.id_mesa ?? null
  reservaAbierta.value = true
}

async function mostrarQr(mesa) {
  mesaQr.value = mesa
  imagenQr.value = ''
  qrAbierto.value = true
  try {
    imagenQr.value = await QRCode.toDataURL(urlQr.value, { width: 320, margin: 2, errorCorrectionLevel: 'M', color: { dark: '#222222', light: '#ffffff' } })
  } catch {
    $q.notify({ type: 'negative', message: 'No se pudo generar el QR de esta mesa.' })
  }
}

async function copiarQr() {
  try { await navigator.clipboard.writeText(urlQr.value); $q.notify({ type: 'positive', message: 'Enlace del QR copiado.' }) }
  catch { $q.notify({ type: 'warning', message: 'No se pudo copiar el enlace.' }) }
}

function imprimirQr() {
  const ventana = window.open('', '_blank', 'width=480,height=600')
  if (!ventana) { $q.notify({ type: 'warning', message: 'Permite las ventanas emergentes para imprimir el QR.' }); return }
  ventana.document.write(`<html><head><title>QR Mesa ${mesaQr.value.numero}</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:36px}img{width:300px;height:300px}p{overflow-wrap:anywhere;color:#555}</style></head><body><h1>SIAR · Mesa ${mesaQr.value.numero}</h1><img src="${imagenQr.value}"/><p>Escanea para ver la carta y pedir desde tu mesa.</p></body></html>`)
  ventana.document.close(); ventana.onload = () => { ventana.focus(); ventana.print() }
}

function eliminar(mesa) {
  $q.dialog({
    title: 'Dar de baja mesa',
    message: `La mesa ${mesa.numero} dejará de aparecer. Su historial de reservas se conserva. ¿Continuar?`,
    cancel: { flat: true, noCaps: true, label: 'Cancelar' },
    ok: { color: 'negative', unelevated: true, noCaps: true, label: 'Dar de baja' },
    persistent: true,
  }).onOk(async () => {
    try {
      const respuesta = await mesasService.eliminarMesa(mesa.id_mesa)
      $q.notify({ type: 'positive', message: respuesta.message })
      cargar()
    } catch (error) {
      $q.notify({ type: 'negative', message: mensajeError(error) })
    }
  })
}

let intervalo = null
onMounted(() => {
  cargar()
  intervalo = setInterval(() => {
    if (!formularioAbierto.value && !reservaAbierta.value) cargar()
  }, 30000)
})
onBeforeUnmount(() => clearInterval(intervalo))
</script>

<style scoped>
.qr-dialog{width:min(440px,94vw);border-radius:14px}.qr-image{width:280px;height:280px;max-width:75vw;object-fit:contain}.qr-url{max-width:360px;overflow-wrap:anywhere}
</style>

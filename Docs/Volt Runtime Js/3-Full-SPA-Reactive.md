# Full SPA Reactive

## Objetivo

Definir y ejecutar la migracion para que VoltStack pueda ofrecer:

- navegacion SPA global desde el primer click
- soporte para vistas tradicionales con y sin layout
- soporte para paginas y zonas reactivas basadas en `Component`
- fallback controlado a recarga completa cuando una respuesta no sea compatible

Este documento funciona como checklist operativo. Cada accion debe marcarse al finalizarse.

## Leyenda

- `[ ]` Pendiente
- `[x]` Finalizado

## Fase 0. Analisis y definicion tecnica

- [x] Confirmar la causa del primer full reload en rutas tradicionales.
- [x] Confirmar que el runtime `volt.js` hoy se inyecta desde `Component` y no desde un punto global del documento.
- [x] Confirmar que las vistas tradicionales pueden navegar por SPA si el runtime esta disponible desde el primer render.
- [x] Definir que el layout no debe ser requisito obligatorio para que una pagina sea SPA-capable.
- [x] Definir que la reactividad server-driven completa debe seguir dependiendo de raices interactivas `data-volt-root`.

## Fase 1. Bootstrap global del runtime SPA

- [x] Extraer la inyeccion automatica de `volt_runtime_script()` fuera de `Component::render()`.
- [x] Definir un mecanismo unico y centralizado para cargar `volt.js` una sola vez por documento.
- [x] Elegir el punto oficial de integracion inicial del runtime:
- [ ] Opcion A: layout base
- [x] Opcion B: inyector global de respuestas HTML
- [x] Asegurar que no existan dobles listeners ni doble inicializacion de `window.Volt`.
- [ ] Verificar que la home y cualquier vista tradicional con enlaces `volt:navigate` naveguen por SPA desde el primer click.

## Fase 2. Soporte SPA para vistas tradicionales con y sin layout

- [ ] Definir el contrato minimo de una respuesta HTML SPA-capable.
- [ ] Establecer si el framework debe inyectar automaticamente el runtime en respuestas HTML tradicionales.
- [x] Implementar el bootstrap para vistas sin layout.
- [ ] Verificar que una vista tradicional con layout siga funcionando correctamente.
- [x] Verificar que una vista tradicional sin layout tambien pueda navegar por SPA si cumple el contrato minimo.
- [ ] Definir el comportamiento cuando una vista HTML no deba participar en SPA.

## Fase 3. Inyector/finalizer de respuestas HTML

- [x] Diseñar un servicio dedicado para post-procesar respuestas HTML.
- [x] Detectar si una `Response` es HTML navegable.
- [x] Detectar si el runtime ya fue inyectado para evitar duplicados.
- [x] Insertar el runtime antes de `</body>` cuando exista.
- [x] Definir un fallback de insercion cuando no exista `</body>`.
- [x] Mantener intactas respuestas JSON, redirects y respuestas del protocolo reactivo.
- [x] Integrar el post-procesado en el flujo del `HttpKernel`.

## Fase 4. Contrato de documento SPA

- [x] Definir metadatos minimos del documento para navegacion SPA.
- [x] Definir una marca estable para distinguir documentos compatibles.
- [ ] Documentar como identificar layout compartido sin volverlo obligatorio.
- [x] Documentar como debe comportarse el runtime cuando detecta cambio estructural fuerte entre documentos.
- [x] Ajustar el runtime para usar ese contrato de forma consistente en navegacion y fallback.

## Fase 5. Reglas de fallback y compatibilidad

- [x] Definir explicitamente los modos `spa`, `reload` y `auto`.
- [x] Permitir que una pagina o respuesta fuerce `reload` de manera declarativa.
- [x] Mantener fallback seguro cuando una respuesta no sea SPA-compatible.
- [x] Verificar que cambios de estructura extrema, documentos especiales o paginas externas usen recarga completa.
- [x] Documentar las razones por las que el runtime decide fallback.
- [x] Definir que paginas de error 404/500 del framework se sirvan como documentos `reload-only`.

## Fase 6. Reactividad server-driven e islas interactivas

- [ ] Mantener la diferencia entre navegacion SPA global y reactividad de componentes.
- [ ] Verificar que las paginas `Component` sigan renderizando sus raices interactivas correctamente.
- [ ] Verificar que una vista tradicional pueda incrustar componentes interactivos sin convertir toda la pagina en `Component`.
- [ ] Confirmar que la hidratacion, snapshots y endpoint `/_volt/action` no dependan del layout.
- [ ] Documentar el patron hibrido: vista tradicional + componentes interactivos embebidos.

## Fase 7. Controladores tradicionales

- [ ] Confirmar que un controlador tradicional puede seguir devolviendo `View` y aun asi participar en navegacion SPA.
- [ ] Verificar que no sea obligatorio migrar todos los controladores a `Component`.
- [ ] Definir cuando conviene convertir una ruta tradicional a pagina reactiva `Component`.
- [ ] Documentar los criterios de eleccion entre:
- [ ] `Controller + View`
- [ ] `Controller + View + componentes interactivos`
- [ ] `Component` como pagina completa

## Fase 8. Pruebas automatizadas

- [ ] Agregar prueba para confirmar que una vista tradicional navega por SPA desde el primer click.
- [x] Agregar prueba para confirmar que una pagina `Component` no duplica el runtime.
- [x] Agregar prueba para respuestas HTML sin layout.
- [x] Agregar prueba para preservar la declaracion documental `reload` al bootstrapear HTML.
- [x] Agregar prueba para documentos `reload-only` declarados por `meta` o por `body`.
- [x] Agregar prueba para documentos especiales `attachment` y respuestas no HTML fuera del contrato SPA.
- [x] Agregar prueba para paginas de error `404` y `500` como documentos `reload-only`.
- [ ] Agregar prueba para preservar el comportamiento actual del protocolo reactivo.
- [ ] Agregar prueba para paginas tradicionales con componentes interactivos embebidos.

## Fase 9. Documentacion final

- [x] Documentar el bootstrap global del runtime.
- [x] Documentar el contrato minimo para vistas tradicionales SPA-capable.
- [x] Documentar el caso de vistas sin layout.
- [x] Documentar el patron de islas reactivas.
- [x] Documentar la diferencia entre navegacion SPA y reactividad server-driven.
- [x] Actualizar ejemplos del skeleton para reflejar el nuevo comportamiento.

## Fase 10. Validacion final

- [ ] Verificar que `/` navegue por SPA desde el primer click.
- [ ] Verificar que rutas tradicionales sigan funcionando aunque no usen layout.
- [ ] Verificar que rutas reactivas sigan funcionando sin regresiones.
- [ ] Verificar que no haya inicializacion duplicada del runtime.
- [ ] Verificar que los hooks `volt:before-navigate` y `volt:navigated` se disparen correctamente en todos los escenarios soportados.
- [ ] Verificar que el comportamiento final sea consistente en desarrollo y en build de produccion.

## Criterios de cierre

La migracion se considerara finalizada cuando se cumpla todo lo siguiente:

- [ ] Toda pagina HTML compatible puede navegar por SPA desde el primer click.
- [ ] El uso de layout deja de ser requisito tecnico para participar en SPA.
- [ ] Las vistas tradicionales siguen pudiendo coexistir con paginas `Component`.
- [ ] La reactividad completa sigue limitada a raices interactivas y componentes server-driven.
- [ ] El framework evita doble carga del runtime.
- [ ] Existen pruebas que cubren los escenarios principales.
- [ ] La documentacion del nuevo flujo queda actualizada.

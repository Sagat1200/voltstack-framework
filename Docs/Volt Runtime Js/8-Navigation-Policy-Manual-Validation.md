# Navigation Policy - Validacion Manual

## Objetivo

Validar en navegador el contrato de politicas configurables por ruta para SPA vs full reload, cubriendo:

- `volt:navigate="spa"` por enlace
- `volt:navigate="reload"` por enlace
- `reload` declarado por el documento destino
- prioridad de resolucion `enlace -> documento -> auto`
- observabilidad de llegada SPA frente a carga documental completa

## Preparacion

1. levantar la aplicacion cliente con servidor local y assets frontend actualizados
2. abrir DevTools en `Network`
3. activar `Preserve log`
4. abrir `/navigationPolicy`

## Pantallas Relevantes

- `/navigationPolicy`
  - laboratorio principal para `spa`, `reload` y `auto`
  - enlaces con markers estables:
    - `data-runtime-check="policy-link-spa"`
    - `data-runtime-check="policy-link-reload"`
    - `data-runtime-check="policy-link-document-reload"`
  - panel de llegada:
    - `data-runtime-check="navigation-arrival-panel"`
    - `data-runtime-check="navigation-arrival-kind"`
    - `data-runtime-check="navigation-arrival-summary"`
    - `data-runtime-check="navigation-arrival-detail"`
- `/navigationDocumentReload`
  - documento `reload-only`
  - marker estable:
    - `data-runtime-check="document-reload-request-marker"`

## Checklist Manual

### 1. Estado Inicial Del Laboratorio

Accion:

- abrir `/navigationPolicy` por carga normal

Esperado:

- el panel `navigation-arrival-panel` muestra una llegada documental inicial
- `navigation-arrival-kind` empieza en `document-load`
- el documento expone `meta name="volt-navigation-mode" content="auto"`

### 2. Politica `spa` Por Enlace

Accion:

- hacer click en el enlace con `data-runtime-check="policy-link-spa"`
- una vez en `/counterExample`, volver al laboratorio por un enlace SPA o por navegacion interna equivalente

Esperado:

- la transicion hacia `/counterExample` ocurre sin full reload del navegador
- al volver a `/navigationPolicy`, el panel `navigation-arrival-panel` muestra llegada SPA
- `navigation-arrival-kind` cambia a `spa`
- `navigation-arrival-summary` describe una llegada por navegacion SPA
- en `navigation-arrival-detail` aparecen campos como `trigger = volt:navigated`, `finalUrl` y `navigationMode`

### 3. Politica `reload` Por Enlace

Accion:

- desde `/navigationPolicy`, hacer click en el enlace con `data-runtime-check="policy-link-reload"`

Esperado:

- el navegador realiza full reload directo hacia `/counterExample`
- la visita no depende del patch SPA del body
- al volver luego al laboratorio, el panel vuelve a reflejar una carga documental, no una llegada SPA arrastrada por ese click

### 4. Politica `reload` Por Documento Destino

Accion:

- desde `/navigationPolicy`, hacer click en el enlace con `data-runtime-check="policy-link-document-reload"`
- anotar el valor visible en `document-reload-request-marker`
- volver al laboratorio con el enlace del destino
- repetir la misma accion una segunda vez

Esperado:

- la visita empieza desde un enlace SPA, pero termina como carga documental completa
- el documento destino expone:
  - `meta name="volt-document" content="reload"`
  - `meta name="volt-navigation-mode" content="reload"`
  - `meta name="volt-cache-control" content="no-store"`
- el `request marker` cambia entre una entrada y otra, confirmando carga real del documento

### 5. Prioridad De Resolucion

Accion:

- comparar los tres casos anteriores desde el mismo laboratorio

Esperado:

- el enlace `spa` fuerza SPA aunque el laboratorio este en modo documental `auto`
- el enlace `reload` fuerza full reload sin esperar al documento destino
- el enlace documental `reload` empieza como SPA pero el destino gana al resolver la respuesta final
- el orden observable coincide con el contrato: `enlace -> documento -> auto`

### 6. Coherencia De Red Y Documento

Accion:

- revisar `Network` mientras ejecutas los pasos 2, 3 y 4

Esperado:

- en el caso `spa`, la experiencia evita una recarga completa del documento final
- en el caso `reload` por enlace, la carga final depende del navegador
- en el caso `reload` por documento, se observa el cambio hacia carga documental completa despues de inspeccionar la respuesta destino

## Criterio De Cierre

Se puede marcar este bloque como completado cuando:

- `spa` por enlace llega y vuelve como SPA de forma consistente
- `reload` por enlace siempre hace carga documental completa
- `reload` por documento se detecta correctamente y termina en full reload
- el panel de llegada de `/navigationPolicy` ayuda a distinguir carga inicial vs llegada SPA
- el `request marker` de `/navigationDocumentReload` cambia en cada entrada completa

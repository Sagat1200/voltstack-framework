# Navigation Contract

## Objetivo

Formalizar el contrato de navegacion SPA del runtime Volt para documentos HTML completos, con y sin layout.

Para una guia practica de integracion desde aplicaciones cliente, ver `5-Client-Integration-Guide.md`.

## Contrato Minimo Del Documento

Un documento se considera SPA-capable cuando cumple lo siguiente:

- expone una respuesta HTML navegable
- incluye el runtime `volt.js`
- declara o hereda el marcador `data-volt-document="spa"` en `body`
- dispone de un modo de navegacion resoluble por el runtime

## Modo De Navegacion

Los modos soportados son:

- `spa`: fuerza navegacion SPA
- `reload`: fuerza recarga completa del documento
- `auto`: deja que el runtime intente navegacion SPA y haga fallback si detecta incompatibilidad

## Prioridad De Resolucion

El runtime resuelve el modo de navegacion en este orden:

1. politica declarada en el enlace (`volt:navigate`, `volt-navigation-mode`)
2. politica declarada por el documento destino (`meta name="volt-navigation-mode"` o `body[data-volt-navigation-mode]`)
3. valor por defecto `auto`

## Marcadores Estables

Los marcadores documentales estables son:

- `data-volt-document="spa"`: indica que el documento participa en el contrato SPA
- `data-volt-document="reload"`: indica que el documento es `reload-only` y no debe ser parcheado por SPA
- `data-volt-navigation-mode="auto|spa|reload"`: politica del documento cuando no se declara por `meta`
- `data-volt-layout="..."`: identidad estructural usada para decidir compatibilidad de layout

## Reglas De Fallback

En modo `auto`, el runtime puede caer a recarga completa por estas razones:

- `layout-mismatch`: el layout actual y el layout destino no coinciden
- `document-reload-only`: el documento destino declara que no participa en SPA
- `document-policy-reload`: el documento destino declara `reload`
- `request-error`: la visita SPA fallo y la opcion `fallback` sigue habilitada

## Compatibilidad Sin Layout

Si una vista no usa layout, el framework puede seguir hacerla SPA-capable mediante el bootstrap global HTML:

- inyecta el runtime
- agrega `data-volt-document="spa"` en `body`
- agrega `data-volt-navigation-mode="auto"` cuando la pagina no declara una politica explicita

Si la vista declara `volt-document=reload`, el bootstrap:

- conserva el documento como `reload-only`
- marca `body` con `data-volt-document="reload"` cuando corresponde
- no fuerza `data-volt-navigation-mode="auto"`

## Enlaces Y Documentos Especiales

El runtime deja la navegacion en manos del navegador cuando el enlace o la respuesta cae fuera del contrato SPA:

- enlaces cross-origin
- enlaces con `download`
- enlaces con `target` distinto de `_self`
- respuestas con `Content-Disposition: attachment`
- respuestas no HTML

Estos casos se consideran documentos especiales o externos y deben terminar en recarga completa o descarga nativa.

## Paginas De Error

Las respuestas HTML de error del framework, como `404` y `500`, se tratan como documentos `reload-only`.

Motivos:

- evitan intentar parchear una vista posiblemente inconsistente o incompleta
- garantizan que la pagina de error se renderice como documento completo
- simplifican el manejo de fallos criticos durante una navegacion SPA

## Reactividad Server-Driven

Este contrato aplica a navegacion SPA documental.

No convierte automaticamente una vista tradicional en una raiz reactiva. Para eso siguen siendo necesarias las
raices `data-volt-root` y el ciclo de hidratacion del runtime.

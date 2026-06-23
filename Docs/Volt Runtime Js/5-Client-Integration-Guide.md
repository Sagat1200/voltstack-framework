# Client Integration Guide

## Objetivo

Explicar como integrar y consumir el contrato SPA reactivo de VoltStack desde una aplicacion cliente.

Esta guia resume el uso recomendado para:

- vistas tradicionales
- paginas `Component`
- documentos `reload-only`
- enlaces SPA
- islas interactivas dentro de vistas no reactivas

## Regla Mental

Separar siempre estos dos conceptos:

- navegacion SPA documental: cambiar de pagina sin full reload
- reactividad server-driven: acciones PHP, snapshots, hidratacion y diffs

Una vista puede participar en SPA sin ser una raiz reactiva completa.

## Opcion 1. Vista Tradicional Navegable Por SPA

Usa este enfoque cuando la pantalla solo necesita SSR normal, pero quieres transiciones SPA entre documentos.

Ejemplo:

```php
final class HomeController extends Controller
{
    public function __invoke(): \Quantum\View\View
    {
        return view('home', [
            'appName' => config('app.name', 'VoltStack'),
        ]);
    }
}
```

Y en la vista:

```html
<a href="/counterExample" volt:navigate>Ir a contador</a>
```

Resultado:

- el documento entra al contrato SPA
- el bootstrap HTML inyecta runtime si hace falta
- la vista sigue siendo tradicional

## Opcion 2. Pagina Reactiva Completa

Usa una pagina `Component` cuando la pantalla necesita:

- `volt-click`
- `volt-model`
- snapshots
- acciones PHP
- hidratacion y efectos

Ejemplo:

```php
final class CounterPage extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
```

Resultado:

- la pagina participa en SPA documental
- ademas expone una raiz reactiva server-driven

## Opcion 3. Vista Tradicional Con Islas Interactivas

Usa este patron cuando no quieres migrar toda la pagina a `Component`.

Recomendado para:

- landing pages con widgets
- dashboards con secciones aisladas
- pantallas mixtas SSR + interaccion

Regla:

- la pagina puede seguir siendo `Controller + View`
- las zonas que necesitan reactividad se montan como componentes interactivos

## Politicas De Navegacion

### Por enlace

```html
<a href="/users" volt:navigate="spa">Usuarios</a>
<a href="/report" volt:navigate="reload">Reporte</a>
<a href="/posts" volt:navigate>Posts</a>
```

Interpretacion:

- `spa`: fuerza SPA
- `reload`: fuerza full reload
- sin valor: usa `auto`

### Por documento

En `head` o por atributo documental:

```html
<meta name="volt-navigation-mode" content="auto">
<meta name="volt-navigation-mode" content="reload">
```

O:

```html
<body data-volt-navigation-mode="auto">
```

## Documentos Reload-Only

Usa este modo cuando una pantalla no debe entrar al flujo SPA.

Ejemplos:

- paginas de error
- exports especiales
- documentos aislados
- vistas con shell incompatible

Declaracion recomendada:

```html
<meta name="volt-document" content="reload">
<meta name="volt-navigation-mode" content="reload">
```

O en `body`:

```html
<body data-volt-document="reload">
```

Resultado:

- el runtime no intenta parchear ese documento
- la navegacion termina en documento completo

## Casos Que Siempre Quedan Fuera De SPA

Estos casos deben seguir usando comportamiento nativo del navegador:

- enlaces externos
- enlaces con `download`
- enlaces con `target` distinto de `_self`
- attachments
- respuestas no HTML

## Que Hace El Framework Automaticamente

Para respuestas HTML completas, el framework:

- inyecta `volt.js` si el documento aun no lo trae
- marca el documento como `spa` por defecto
- agrega `data-volt-navigation-mode="auto"` cuando no existe una politica declarada
- respeta `reload-only` si el documento ya lo declara

## Cuando Usar Layout

El layout sigue siendo recomendable para:

- shell visual compartido
- `head` comun
- `data-volt-layout`
- portales, banners y modales

Pero ya no es requisito tecnico para participar en SPA.

## Recomendacion De Arquitectura

Usa esta matriz de decision:

- `Controller + View`: contenido SSR, lectura, marketing, docs
- `Controller + View + islas`: paginas mixtas
- `Component`: pantallas con interaccion server-driven fuerte
- `reload-only`: errores, exports, documentos fuera del shell

## Checklist Rapido

- si quieres SPA: usa enlaces `volt:navigate`
- si quieres reactividad completa: usa `Component`
- si no quieres SPA: declara `volt-document=reload`
- si la pantalla es especial o critica: considera `reload-only`
- si la pagina no usa layout: deja que el bootstrap HTML complete el contrato

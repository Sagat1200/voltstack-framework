# VoltStack Framework

VoltStack es un framework PHP con un runtime reactivo guiado por servidor, efectos de protocolo granulares y una DSL fluida en backend para transiciones, efectos manuales y politicas de runtime.

## Vision General Del Protocolo Reactivo

VoltStack usa un protocolo reactivo guiado por servidor entre el runtime de frontend y el runtime de backend.

- El cliente envia un snapshot del componente, el nombre de la accion, parametros opcionales y cualquier actualizacion de estado pendiente.
- El servidor hidrata el componente, aplica cambios, ejecuta la accion, vuelve a renderizar y genera los efectos del protocolo.
- La respuesta puede incluir HTML, efectos granulares como `text.update` o `attribute.set`, instrucciones de navegacion y efectos de politicas de runtime.
- El runtime de frontend aplica esos efectos de forma conservadora, preservando foco, seleccion, scroll y la semantica del ciclo de vida de las requests cuando es posible.

Flujo tipico:

```txt
Interaccion del usuario
-> Request del runtime frontend
-> Hidratacion del componente
-> Ejecucion de la accion
-> Generacion de diff/efectos
-> Aplicacion de patch/efectos en frontend
```

## Patrones Recomendados Para Builders

Cuando uses builders basados en callbacks, conviene preferir closures tipadas para que el autocompletado del IDE pueda resolver correctamente los metodos del builder.

### Efectos Manuales

```php
use VoltStack\Runtime\Protocol\ActionEffectOptions;
use VoltStack\Runtime\Protocol\ActionManualEffectBuilder;

return ActionEffectOptions::make()
    ->effects(fn(ActionManualEffectBuilder $effects) => $effects
        ->onTarget('title-input')
        ->focusAndSetAttribute('data-last-save', (string) time())
        ->event('demo.saved', ['count' => $this->count]));
```

### Transiciones

```php
use VoltStack\Runtime\Protocol\ActionEffectOptions;
use VoltStack\Runtime\Protocol\ActionTransitionBuilder;

return ActionEffectOptions::make()
    ->transitions(fn(ActionTransitionBuilder $transitions) => $transitions
        ->onTarget('count')
        ->forTextUpdate()
        ->pop(220)
        ->onTarget('count')
        ->forTextUpdate()
        ->updateAs('glow', className: 'volt-transition-soft-edge'));
```

### Politicas De Runtime

```php
use VoltStack\Runtime\Protocol\ActionEffectOptions;
use VoltStack\Runtime\Protocol\ActionRuntimePolicyBuilder;

return ActionEffectOptions::make()
    ->policies(fn(ActionRuntimePolicyBuilder $policies) => $policies
        ->onTarget('title')
        ->dirty('200ms')
        ->onTarget('save-form')
        ->forSave()
        ->success('200ms', '1.2s')
        ->error('3s'));
```

### Por Que Usar Callbacks Tipados

- Mejora el autocompletado del IDE para los metodos fluidos del builder.
- Evita falsos avisos de `Undefined method` en atajos como `forSave()`.
- Mantiene los bloques basados en callbacks consistentes con los tests del protocolo reactivo y con los ejemplos de la app.

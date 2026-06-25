## Validacion Manual Rapida Del Runtime

Usar esta tabla para registrar una pasada corta de validacion funcional en navegador real sobre el runtime SPA/reactivo.

### Datos De La Ejecucion

| Campo | Valor |
| --- | --- |
| Fecha |  |
| Entorno |  |
| Branch o commit |  |
| Navegador |  |
| Tester |  |

### Matriz De Validacion

| ID | Area | Escenario | Ruta o pantalla | Esperado | Estado | Observaciones |
| --- | --- | --- | --- | --- | --- | --- |
| QA-01 | Runtime asset | El runtime se sirve como recurso externo | `/runtimeEvents` | Existe request a `/_volt/runtime.js` y no viaja inline dentro del HTML principal | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-02 | Cache HTTP | El runtime expone headers cacheables | `/_volt/runtime.js` | Respuesta con `Cache-Control`, `ETag` y `Last-Modified` | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-03 | Telemetria inicial | El panel de eficiencia refleja metricas reales | `/runtimeEvents` | Se muestran datos en `efficiency-navigation-performance`, `efficiency-runtime-asset` y `efficiency-runtime-overview` | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-04 | Accion reactiva | Una interaccion simple actualiza telemetria y patch | `/runtimeEvents` | `Telemetry patch` y `Telemetry action` incrementan y aparece `Latest patch entry` | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-05 | Model sync | Escritura rapida no genera tormenta de requests | `/runtimeModelSync` | Requests razonables, sin duplicacion injustificada ni bloqueo visible | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-06 | Navegacion SPA | Navegar entre pantallas sin recarga completa | `/runtimeEvents` -> `/runtimeModelSync` -> `/runtimeState` | La navegacion ocurre por SPA y `Telemetry navigation` incrementa | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-07 | Persistencia del runtime | El runtime no se reinyecta incorrectamente al navegar | Flujo SPA entre rutas demo | El runtime sigue operativo y no reaparece inline como parte del HTML documental | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-08 | Abort y stale | Requests concurrentes se resuelven de forma coherente | Acciones o navegaciones rapidas consecutivas | No quedan estados colgados y el resultado final visible es coherente | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-09 | Estado loading | El estado `loading` aparece y desaparece correctamente | Cualquier accion reactiva | Se activa durante la request y se limpia al finalizar | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-10 | Estado dirty | El estado `dirty` responde a cambios de entrada | Inputs con `volt:model` o `volt:model.sync` | Se activa al editar y se limpia cuando corresponde | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-11 | Estado success | El estado `success` respeta timeout y limpieza | Acciones con resultado correcto | Se activa de forma visible y luego se limpia segun politica configurada | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-12 | Estado error | El estado `error` se refleja de forma segura | Error forzado de request o protocolo | Se informa error sin romper la UI ni dejar estados inconsistentes | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-13 | Foco | El foco se preserva tras patch reactivo | Inputs y formularios | El cursor no salta de forma inesperada despues del patch | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-14 | Scroll | El scroll no se rompe durante patch o SPA | Contenido con desplazamiento | No hay saltos visuales inesperados salvo comportamiento documentado | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |
| QA-15 | Reset operativo | Resetear telemetria no rompe el estado del runtime | `/runtimeEvents` | `Resetear telemetria` limpia contadores y `Refrescar roots` mantiene datos coherentes | `[ ] OK` `[ ] Parcial` `[ ] Falla` |  |

### Hallazgos

| Prioridad | Hallazgo | Impacto | Reproducible | Ruta | Nota tecnica |
| --- | --- | --- | --- | --- | --- |
| Alta |  |  | `[ ] Si` `[ ] No` |  |  |
| Media |  |  | `[ ] Si` `[ ] No` |  |  |
| Baja |  |  | `[ ] Si` `[ ] No` |  |  |

### Resumen De La Pasada

| Campo | Valor |
| --- | --- |
| Total escenarios | 15 |
| OK |  |
| Parcial |  |
| Falla |  |
| Riesgo general | `[ ] Bajo` `[ ] Medio` `[ ] Alto` |
| Siguiente bloque recomendado |  |

### Decision Tecnica

- continuar sin cambios
- corregir issues criticos antes de nuevas features
- priorizar resiliencia (`timeout`, `retry`, clasificacion de errores)
- priorizar UX runtime (`focus`, `scroll`, `states`)
- priorizar performance (`payload`, `patch`, `volt.js`)

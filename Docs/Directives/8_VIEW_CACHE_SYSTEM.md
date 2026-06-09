# 07_VIEW_CACHE_SYSTEM.md

# VoltStack View Cache System

---

# Estado Actual Implementado

Este documento describe una arquitectura de cache más amplia que la implementación real actual. Hoy el framework ya cuenta con un sistema funcional de vistas compiladas, pero no todavía con metadata avanzada, dependency tracking completo, manifest ni invalidación por grafo de dependencias.

Estado real actual:

* el cache de vistas compiladas está concentrado en `CompiledViewStore`
* el archivo compilado se calcula con `md5($sourcePath . '|' . $compiler->version()) . '.php'`
* la expiración se decide comparando `filemtime()` entre la vista fuente y la vista compilada
* el compilado escrito incluye un encabezado con `Source` y `Compiler`
* existe limpieza del cache mediante `CompiledViewStore::clear()`
* existen los comandos `view:cache` y `view:clear`

Capacidades reales actuales:

* compilación bajo demanda al renderizar
* precompilación mediante comando
* limpieza de cache compilada
* separación por versión del compilador en el nombre del archivo compilado
* escritura de archivos compilados en el directorio configurado

Lo que NO existe todavía:

* manifest de cache
* dependency tracking de includes y layouts para invalidación cascada
* checksum del contenido fuente como estrategia primaria de validación
* metadata persistida separadamente por vista
* invalidación por cambios estructurales del árbol de dependencias
* cache distribuido o estrategia explícita de alta concurrencia

Referencia real del código actual:

* [CompiledViewStore.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Cache/CompiledViewStore.php)
* [ViewCacheCommand.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/Console/Commands/ViewCacheCommand.php)
* [ViewClearCommand.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/Console/Commands/ViewClearCommand.php)
* [PhpViewEngine.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/PhpViewEngine.php)
* [CompiledViewRenderingTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Feature/CompiledViewRenderingTest.php)

---

# 1. Introducción

El View Cache System de VoltStack es el sistema responsable de almacenar, invalidar y administrar vistas compiladas.

Su propósito principal es eliminar el costo de compilación durante runtime, permitiendo que las vistas sean compiladas una sola vez y reutilizadas posteriormente.

El sistema de cache es crítico para:

* rendimiento SSR
* reducción de CPU
* menor latencia
* escalabilidad
* renderizado masivo
* aplicaciones enterprise

---

# 2. Objetivos del Sistema

El sistema de cache debe:

* almacenar vistas compiladas
* invalidar automáticamente cambios
* minimizar recompilaciones
* soportar cache persistente
* soportar precompilación
* soportar lazy compilation
* mantener integridad
* soportar alta concurrencia futura

---

# 3. Filosofía Arquitectónica

VoltStack compila templates previamente.

Nunca debe interpretar templates directamente durante render runtime.

---

# Flujo

```text id="mgk8x0"
Template
   ↓
Compiler
   ↓
Compiled PHP
   ↓
Cache Storage
   ↓
Runtime Renderer
```

---

# 4. Beneficios del Cache

| Beneficio        | Descripción               |
| ---------------- | ------------------------- |
| Menor CPU        | Evita recompilaciones     |
| Mayor velocidad  | PHP compilado listo       |
| SSR rápido       | Render inmediato          |
| Escalabilidad    | Menor overhead            |
| Mejor throughput | Más requests concurrentes |

---

# 5. Arquitectura General

```text id="mgk5u7"
ViewCacheSystem
 ├── CacheManager
 ├── CacheStore
 ├── CacheCompiler
 ├── CacheValidator
 ├── CacheMetadata
 ├── CacheInvalidator
 └── CacheManifest
```

---

# 6. Flujo General

```text id="mgk1v8"
View Request
    ↓
Cache Exists?
    ↓
YES → Cache Valid?
    ↓
YES → Use Cached View
NO  → Recompile
```

---

# 7. Cache Storage

Las vistas compiladas deben almacenarse en:

```text id="mgk9w1"
storage/framework/views
```

---

# Ejemplo

```text id="mgk3y2"
storage/framework/views/
    a1b2c3d4.php
    e5f6g7h8.php
```

---

# 8. Cache Naming Strategy

El nombre del archivo cacheado debe derivarse de:

* path original
* checksum
* hash estable

---

# Ejemplo

```text id="mgk5z4"
md5(template_path)
```

---

# Resultado

```text id="mgk7p8"
a1b2c3d4e5.php
```

---

# 9. Cache Metadata

Cada vista compilada debe almacenar metadata.

---

# Metadata Recomendada

| Campo        | Descripción       |
| ------------ | ----------------- |
| sourcePath   | Path original     |
| compiledPath | Path compilado    |
| checksum     | Hash del template |
| compiledAt   | Timestamp         |
| version      | Compiler version  |
| dependencies | Includes/layouts  |

---

# 10. Cache Validation

El sistema debe validar si la vista compilada sigue siendo válida.

---

# Validaciones

* timestamp
* checksum
* dependencies
* compiler version

---

# Ejemplo

```text id="mgk2r7"
Template Modified?
    ↓
YES → Recompile
```

---

# 11. Dependency Tracking

El sistema debe rastrear dependencias.

---

# Ejemplo

```volt id="mgk4f6"
@include('partials.header')
```

Si `partials.header` cambia:

```text id="mgk9j5"
Parent cache invalidated
```

---

# 12. Layout Dependency Tracking

Layouts también invalidan hijos.

---

# Ejemplo

```volt id="mgk6m1"
@extends('layouts.app')
```

Si cambia el layout:

```text id="mgk8q4"
All child templates invalidated
```

---

# 13. Cache Compilation Flow

---

# Flujo Completo

```text id="mgk5n9"
Template Request
    ↓
Find Compiled View
    ↓
Validate Cache
    ↓
Invalid?
    ↓
YES → Compile
NO  → Render
```

---

# 14. Lazy Compilation

VoltStack debe soportar compilación bajo demanda.

---

# Objetivo

Solo compilar vistas utilizadas.

---

# Flujo

```text id="mgk1s3"
First Request
    ↓
Compile View
    ↓
Cache Result
```

---

# 15. Precompilation

VoltStack debe soportar precompilación futura.

---

# Comando Futuro

```bash id="mgk4t8"
php voltstack view:cache
```

---

# Objetivos

* deployments rápidos
* SSR optimizado
* producción enterprise

---

# 16. Cache Invalidation

---

# 16.1 Template Change

Si cambia el template:

```text id="mgk9u0"
Invalidate cache
```

---

# 16.2 Dependency Change

Si cambia include/layout:

```text id="mgk7v3"
Invalidate dependent views
```

---

# 16.3 Compiler Version Change

Si cambia el compiler:

```text id="mgk5g2"
Invalidate all cache
```

---

# 17. Cache Cleanup

El sistema debe soportar limpieza.

---

# Comando Futuro

```bash id="mgk3d9"
php voltstack view:clear
```

---

# Objetivos

* eliminar cache obsoleto
* liberar espacio
* evitar corrupción

---

# 18. Runtime Cache Integration

El runtime utilizará únicamente vistas compiladas válidas.

---

# Flujo

```text id="mgk1h6"
Renderer
   ↓
Compiled View
   ↓
Execute PHP
```

---

# 19. Cache Isolation

Cada vista debe aislarse.

---

# Objetivos

* evitar colisiones
* evitar corrupción
* soportar concurrencia futura

---

# 20. Compiler Fingerprinting

Cada compilación debe contener información del compiler.

---

# Ejemplo

```text id="mgk6c8"
compiler_version
compiler_hash
build_id
```

---

# 21. Cached View Structure

Las vistas compiladas pueden incluir encabezados metadata.

---

# Ejemplo

```php id="mgk9n1"
/**
 * VoltStack Compiled View
 * Source: resources/views/home.volt
 * Compiled: 2026-06-05
 * Hash: abc123
 */
```

---

# 22. Performance Goals

---

# 22.1 Zero Runtime Parsing

Nunca parsear templates en runtime.

---

# 22.2 Minimal File IO

Reducir lecturas innecesarias.

---

# 22.3 Fast Validation

Checks rápidos.

---

# 22.4 Efficient Dependency Resolution

Dependencias optimizadas.

---

# 23. Error Handling

---

# 23.1 Missing Compiled View

```text id="mgk5x3"
Compiled view missing.
```

---

# 23.2 Corrupted Cache

```text id="mgk7z0"
Invalid compiled template.
```

---

# 23.3 Invalid Dependency

```text id="mgk1q4"
Dependency resolution failed.
```

---

# 24. Security Considerations

---

# 24.1 Cache Isolation

Evitar ejecución de archivos externos.

---

# 24.2 Writable Protection

Validar paths permitidos.

---

# 24.3 Compiled PHP Integrity

Verificar integridad opcional futura.

---

# 25. Compatibilidad Futura

El sistema debe prepararse para:

* component cache
* hydration cache
* reactive cache
* SSR cache
* edge rendering
* distributed cache
* memory cache
* async compilation

---

# 26. Arquitectura de Escalabilidad

Futuras versiones podrían soportar:

| Sistema         | Objetivo          |
| --------------- | ----------------- |
| Redis Cache     | Cache distribuido |
| Memory Cache    | Runtime acelerado |
| Build Cache     | Deployments       |
| CDN Integration | Edge rendering    |

---

# 27. Estructura Recomendada

```text id="mgk4l7"
src/
└── Quantum/
    └── View/
        ├── Cache/
        │   ├── Manager/
        │   ├── Store/
        │   ├── Validator/
        │   ├── Metadata/
        │   ├── Invalidator/
        │   ├── Manifest/
        │   ├── Contracts/
        │   ├── Exceptions/
        │   └── Support/
```

---

# 28. Ejemplo Completo

---

# Template

```volt id="mgk9f2"
@include('partials.header')
```

---

# Compilación

```text id="mgk7m6"
home.volt
    ↓
home.compiled.php
```

---

# Runtime

```text id="mgk1b5"
Use Cached View
```

---

# Cambio Detectado

```text id="mgk5u1"
partials.header changed
```

---

# Resultado

```text id="mgk8e9"
Invalidate dependent cache
```

---

# 29. Objetivo Estratégico

El View Cache System representa una de las bases de rendimiento del runtime de VoltStack.

Toda futura capacidad del framework dependerá de este sistema:

* SSR
* SPA híbrido
* hidratación
* componentes
* rendering concurrente
* edge rendering

Por ello, el sistema debe diseñarse desde el inicio como una arquitectura:

* desacoplada
* extensible
* altamente optimizable
* enterprise-ready
* preparada para evolución progresiva

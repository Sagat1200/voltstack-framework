# 09_TESTING_STRATEGY.md

# VoltStack Testing Strategy

---

# Estado Actual Implementado

Este documento plantea una estrategia de testing más amplia que la suite real actual. Hoy el framework ya tiene cobertura útil sobre el compilador, el pipeline mínimo, el runtime y el cache de vistas compiladas, pero no todavía una estrategia formal completa con snapshots dedicados, stress testing y performance benchmarking sistemáticos.

Estado real actual:

* la validación principal se hace con PHPUnit
* existe cobertura unitaria del pipeline mínimo en `TemplatePipelineTest`
* existe cobertura unitaria end-to-end del compilador en `ViewCompilerTest`
* existe cobertura feature del render compilado, layouts, includes, loops, `forelse` y errores de runtime en `CompiledViewRenderingTest`
* existe cobertura feature del flujo base de vistas y prioridad `.volt.php` en `ViewRenderingTest`

Cobertura real actual:

* tokenización inline y segmentación fuente/PHP
* parseo a `TemplateNode`
* construcción jerárquica con `TemplateBlockParser`
* compilación con `TemplateNodeCompiler` y `ViewCompiler`
* metadata `line` y `column`
* excepciones especializadas del compilador
* preservación de excepciones en runtime
* cache de vistas compiladas

Lo que NO existe todavía como suite formal separada:

* snapshot tests dedicados como categoría estable
* benchmarking de performance automatizado
* stress tests de templates complejos como suite independiente
* matriz de compatibilidad o golden tests extensivos para todas las directivas

Referencia real de la suite actual:

* [TemplatePipelineTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Unit/TemplatePipelineTest.php)
* [ViewCompilerTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Unit/ViewCompilerTest.php)
* [CompiledViewRenderingTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Feature/CompiledViewRenderingTest.php)
* [ViewRenderingTest.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/tests/Feature/ViewRenderingTest.php)

---

# 1. Introducción

La estrategia de testing de VoltStack define cómo validar la estabilidad, integridad y rendimiento del sistema de templates.

El sistema de directivas representa uno de los núcleos críticos del framework y requiere un enfoque robusto de testing.

---

# 2. Objetivos

El sistema de testing debe:

* validar compilación correcta
* validar AST
* validar render output
* validar cache
* validar errores
* validar performance
* soportar regresión testing
* garantizar estabilidad enterprise

---

# 3. Filosofía de Testing

Cada capa debe probarse independientemente.

```text id="mgk7q5"
Lexer Tests
Parser Tests
AST Tests
Compiler Tests
Runtime Tests
Cache Tests
Integration Tests
```

---

# 4. Tipos de Testing

| Tipo              | Objetivo             |
| ----------------- | -------------------- |
| Unit Tests        | Componentes aislados |
| Integration Tests | Flujo completo       |
| Snapshot Tests    | Output compilado     |
| Performance Tests | Rendimiento          |
| Regression Tests  | Evitar rupturas      |
| Stress Tests      | Templates complejos  |

---

# 5. Lexer Testing

---

# Objetivos

* tokenización correcta
* detección de directivas
* manejo de texto plano

---

# Ejemplo

Entrada:

```volt id="mgk5r8"
@if($user)
@endif
```

Salida esperada:

```text id="mgk3h2"
T_IF
T_ENDIF
```

---

# 6. Parser Testing

---

# Objetivos

* AST correcto
* nesting válido
* detección de errores

---

# Ejemplo

```text id="mgk9v9"
TemplateNode
 └── IfNode
```

---

# 7. AST Testing

---

# Objetivos

* jerarquía válida
* relaciones padre/hijo
* metadata correcta

---

# Validaciones

* node types
* line mapping
* children integrity

---

# 8. Compiler Testing

---

# Objetivos

* PHP válido
* compilación correcta
* output consistente

---

# Ejemplo

Template:

```volt id="mgk7k1"
{{ $name }}
```

Resultado esperado:

```php id="mgk1c4"
<?= e($name) ?>
```

---

# 9. Runtime Testing

---

# Objetivos

* render correcto
* variables inyectadas
* layouts funcionales
* includes funcionales

---

# Resultado esperado

```html id="mgk5o7"
Hello Francisco
```

---

# 10. Cache Testing

---

# Objetivos

* invalidación correcta
* recompilación correcta
* dependency tracking

---

# Casos

* template modificado
* include modificado
* layout modificado

---

# 11. Error Testing

---

# Objetivos

* errores precisos
* line mapping correcto
* excepciones correctas

---

# Ejemplo

```volt id="mgk9n4"
@if($user)
```

Resultado esperado:

```text id="mgk6t3"
Unclosed @if directive
```

---

# 12. Snapshot Testing

---

# Objetivo

Comparar output compilado esperado.

---

# Flujo

```text id="mgk8m7"
Template
 ↓
Compile
 ↓
Compare Snapshot
```

---

# Beneficios

* detectar regresiones
* detectar cambios inesperados

---

# 13. Integration Testing

---

# Objetivos

Validar pipeline completo:

```text id="mgk3b0"
Template
 ↓
Lexer
 ↓
Parser
 ↓
AST
 ↓
Compiler
 ↓
Runtime
 ↓
HTML
```

---

# 14. Performance Testing

---

# Objetivos

* velocidad de compilación
* velocidad render
* uso de memoria

---

# Métricas

| Métrica      | Objetivo |
| ------------ | -------- |
| Compile Time | Bajo     |
| Render Time  | Bajo     |
| Memory Usage | Estable  |

---

# 15. Stress Testing

---

# Objetivos

Validar:

* templates grandes
* nesting profundo
* includes masivos

---

# 16. Regression Testing

---

# Objetivos

Evitar rupturas futuras.

---

# Estrategia

Cada bug corregido debe incluir test.

---

# 17. Coverage Goals

Cobertura mínima recomendada:

| Sistema  | Cobertura |
| -------- | --------- |
| Lexer    | 95%       |
| Parser   | 95%       |
| Compiler | 95%       |
| Runtime  | 90%       |
| Cache    | 90%       |

---

# 18. Test Isolation

Cada test debe ser aislado.

---

# Objetivos

* evitar contaminación
* reproducibilidad
* paralelización futura

---

# 19. Fixtures

Templates reutilizables para testing.

---

# Ejemplo

```text id="mgk7x8"
tests/Fixtures/views/
```

---

# 20. Golden Files

Archivos esperados compilados.

---

# Ejemplo

```text id="mgk1d5"
tests/Fixtures/compiled/
```

---

# 21. Future Testing

Preparar arquitectura para:

* component testing
* hydration testing
* SPA runtime testing
* reactive runtime testing
* SSR streaming testing

---

# 22. CI/CD Integration

Compatibilidad futura con:

* GitHub Actions
* GitLab CI
* automated snapshots
* performance benchmarks

---

# 23. Estructura Recomendada

```text id="mgk5f9"
tests/
├── Unit/
│   ├── Lexer/
│   ├── Parser/
│   ├── AST/
│   ├── Compiler/
│   ├── Runtime/
│   └── Cache/
│
├── Integration/
│
├── Fixtures/
│
├── Snapshots/
│
└── Performance/
```

---

# 24. Objetivo Estratégico

La estrategia de testing representa la garantía de estabilidad del runtime de VoltStack.

Toda futura evolución del framework dependerá de una base sólida de testing.

Por ello, el sistema debe diseñarse para ser:

* automatizable
* extensible
* reproducible
* enterprise-ready
* preparado para evolución progresiva

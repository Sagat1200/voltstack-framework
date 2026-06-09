# 08_ERROR_HANDLING.md

# VoltStack Error Handling System

---

# Estado Actual Implementado

El sistema real ya tiene una base de errores especializada, aunque todavía no toda la jerarquía idealizada en este documento.

Excepciones implementadas hoy:

* `TemplateCompilerException`: base para errores del compilador
* `TemplateParseException`: errores de tokenización, parseo y compilación estructural
* `DirectiveBalanceException`: errores de balanceo y cierre de directivas
* `ViewRenderException`: errores de ejecución/render de vistas
* `ViewNotFoundException`: resolución de vistas inexistentes

Capacidades implementadas hoy:

* mensajes con `line` y `column`
* contexto de archivo fuente cuando la compilación conoce el `sourcePath`
* preservación de excepciones del compilador dentro de vistas anidadas
* preservación de `ViewRenderException` entre vistas padre e hijas
* separación entre errores de compilación y errores de runtime

Ejemplos reales actuales:

```text
Unclosed @if directive at line 1, column 1 in [resources/views/home.volt.php].
```

```text
The @forelse directive requires an @empty block at line 1, column 1.
```

```text
Unable to render view [C:\...\resources\views\partials\note.volt.php].
```

Limitaciones actuales:

* no existe todavía una jerarquía completa por lexer/parser/AST/compiler/cache/runtime
* no existe un contrato formal común tipo `ViewExceptionContract`
* no existe render enriquecido con fragmento de código o línea resaltada
* no existe mapping completo desde PHP compilado hacia línea exacta del template

Referencia real del código actual:

* [TemplateCompilerException.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Exceptions/TemplateCompilerException.php)
* [TemplateParseException.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Exceptions/TemplateParseException.php)
* [DirectiveBalanceException.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Exceptions/DirectiveBalanceException.php)
* [ViewRenderException.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Exceptions/ViewRenderException.php)
* [TemplateDirectiveCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateDirectiveCompiler.php)
* [TemplateBlockParser.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/TemplateBlockParser.php)
* [ViewCompiler.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/Compilers/ViewCompiler.php)
* [PhpViewEngine.php](file:///c:/W4/Packages/VoltStack/voltstack-framework/src/Quantum/View/PhpViewEngine.php)

---

# 1. Introducción

El Error Handling System de VoltStack es responsable de detectar, capturar, estructurar y reportar errores relacionados con:

* parsing
* compilación
* renderizado
* directivas
* layouts
* includes
* runtime execution

El objetivo es proporcionar errores claros, precisos y orientados al desarrollador.

---

# 2. Objetivos del Sistema

El sistema debe:

* detectar errores tempranamente
* proporcionar mensajes claros
* preservar line mapping
* identificar archivos exactos
* soportar debugging
* desacoplar errores por capas
* permitir reporting enterprise

---

# 3. Filosofía Arquitectónica

Cada etapa del pipeline debe manejar errores independientemente.

```text id="mgk1e4"
Lexer Errors
Parser Errors
AST Errors
Compiler Errors
Runtime Errors
Cache Errors
```

---

# 4. Arquitectura General

```text id="mgk5w8"
ViewException
 ├── LexerException
 ├── ParserException
 ├── ASTException
 ├── CompilerException
 ├── RuntimeException
 ├── DirectiveException
 ├── CacheException
 └── RenderException
```

---

# 5. Lexer Errors

Errores durante tokenización.

---

# Ejemplos

```text id="mgk8r3"
Unexpected token.
```

```text id="mgk3p1"
Invalid echo syntax.
```

---

# 6. Parser Errors

Errores estructurales.

---

# Ejemplos

```text id="mgk9g2"
Unclosed @if directive.
```

```text id="mgk7t0"
Unexpected @endif.
```

---

# 7. AST Errors

Errores en construcción del árbol.

---

# Ejemplos

```text id="mgk5m7"
Invalid node hierarchy.
```

---

# 8. Compiler Errors

Errores de compilación.

---

# Ejemplos

```text id="mgk2u9"
Unable to compile directive [foreach]
```

---

# 9. Runtime Errors

Errores durante render.

---

# Ejemplos

```text id="mgk1k6"
View [dashboard] not found.
```

```text id="mgk4v5"
Undefined variable [$user]
```

---

# 10. Directive Errors

Errores relacionados a directivas.

---

# Ejemplos

```text id="mgk8n8"
Unknown directive [custom]
```

```text id="mgk6h3"
Directive must implement DirectiveContract
```

---

# 11. Include Errors

---

# Ejemplo

```text id="mgk9q1"
Included view [partials.header] not found.
```

---

# 12. Layout Errors

---

# Ejemplo

```text id="mgk7b9"
Layout [layouts.app] not found.
```

---

# 13. Cache Errors

---

# Ejemplos

```text id="mgk5d2"
Compiled cache corrupted.
```

```text id="mgk3z4"
Unable to write compiled template.
```

---

# 14. Error Metadata

Todos los errores deben incluir:

| Campo     | Descripción           |
| --------- | --------------------- |
| message   | Error principal       |
| file      | Archivo fuente        |
| line      | Línea original        |
| column    | Columna               |
| directive | Directiva involucrada |
| template  | Template afectado     |

---

# 15. Error Formatting

---

# Ejemplo

```text id="mgk1y7"
ParserException

Unclosed @foreach directive.

File:
resources/views/users/index.volt

Line:
52
```

---

# 16. Compiler Line Mapping

El compiler debe preservar referencias originales.

---

# Objetivo

Errores precisos aún usando PHP compilado.

---

# 17. Debug Mode

Modo debug futuro:

```env id="mgk6f1"
APP_DEBUG=true
```

---

# Funcionalidades

* stack traces
* fragmentos del template
* líneas resaltadas
* metadata runtime

---

# 18. Production Mode

En producción:

* errores sanitizados
* sin stack traces
* logging interno

---

# 19. Logging

Compatibilidad futura con:

* file logs
* centralized logging
* observability systems
* enterprise monitoring

---

# 20. Exception Contracts

Todas las excepciones deben implementar:

```php id="mgk8x6"
ViewExceptionContract
```

---

# 21. Recovery Strategy

Errores recuperables futuros:

* includes opcionales
* cache regeneration
* lazy recompilation

---

# 22. Compatibilidad Futura

El sistema debe prepararse para:

* hydration errors
* SPA runtime errors
* reactive runtime errors
* component errors
* async rendering errors

---

# 23. Estructura Recomendada

```text id="mgk2m3"
src/
└── Quantum/
    └── View/
        ├── Exceptions/
        │   ├── Lexer/
        │   ├── Parser/
        │   ├── Compiler/
        │   ├── Runtime/
        │   ├── Cache/
        │   └── Support/
```

---

# 24. Objetivo Estratégico

El Error Handling System debe proporcionar una experiencia enterprise-ready para debugging y observabilidad.

La meta es construir un sistema:

* claro
* preciso
* extensible
* desacoplado
* preparado para runtimes avanzados

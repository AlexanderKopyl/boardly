# Generic SOLID And Design Principle Rules

Status: reusable architecture rulebook  
Scope: project-agnostic SOLID and design-principle rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

SOLID and design principles must be treated as practical production rules, not decorative theory.

Use these principles to keep code easy to change, test, review, and reason about.

## 2. Single Responsibility Principle

A class should have one clear reason to change.

Split code when one class mixes unrelated responsibilities:

```text
- input/output transport;
- use-case orchestration;
- domain rules;
- persistence;
- external integrations;
- queue publishing;
- cache/search updates;
- API documentation;
- logging;
- serialization;
- authorization;
- validation.
```

A class may call collaborators, but it should not own their responsibilities.

## 3. Open/Closed Principle

Code should be open for extension and closed for repeated modification when new cases are expected to grow.

Use extension points when variation is real:

```text
- interfaces;
- strategy classes;
- registries;
- tagged services;
- factories;
- mappers;
- policies.
```

Do not create central switch/match/if-instanceof registries that must be edited for every new module-specific case.

A one-off conditional is acceptable when the variation is genuinely limited.

## 4. Liskov Substitution Principle

Subtypes/implementations must honor the contract they implement.

Rules:

```text
- Do not narrow valid inputs unexpectedly.
- Do not return incompatible outputs.
- Do not throw unexpected domain/control-flow exceptions that violate the contract.
- Do not implement an interface with a fake/no-op adapter unless the contract allows it.
```

## 5. Interface Segregation Principle

Prefer small, focused interfaces.

Do not create large interfaces that force implementations to support unrelated operations.

Bad signs:

```text
- interface with many unrelated methods;
- implementations throwing not-supported exceptions;
- one interface mixing reads, writes, external calls, cache, and logging;
- generic ServiceInterface with unclear responsibility.
```

## 6. Dependency Inversion Principle

High-level policy should not depend on low-level technical details.

Application and Domain should depend on abstractions when crossing technical boundaries.

Infrastructure implements those abstractions.

Good shape:

```text
Application -> PortInterface
Infrastructure -> Adapter implements PortInterface
```

Bad shape:

```text
Application -> ConcreteExternalClient
Domain -> FrameworkHttpClient
```

## 7. Avoid god classes

Avoid broad classes with unclear responsibility.

Suspicious names:

```text
Manager
Helper
Utils
BaseService
AbstractProcessor
CommonService
MainService
Facade
Processor
Handler
```

These names are not always forbidden, but they require strong justification and a clear responsibility.

Prefer names that describe the exact role.

## 8. Avoid premature abstraction

Do not create abstractions before there is a real reason.

An abstraction is justified when:

```text
- two or more real implementations exist or are clearly imminent;
- it protects an architectural boundary;
- it hides framework/infrastructure details from core code;
- it makes testing a use case materially simpler;
- it expresses a stable business/application capability.
```

An abstraction is weak when:

```text
- it wraps one class with no boundary benefit;
- it exists only to look clean;
- it mirrors a vendor library API;
- it creates more names than behavior;
- it hides code instead of clarifying it.
```

## 9. Cohesion rule

Code that changes together should live together.

Code that changes for different reasons should be separated.

A folder is not cohesive just because files share a technical type such as Service, Controller, Entity, or Repository.

Prefer business/use-case cohesion over technical grouping.

## 10. Coupling rule

Reduce coupling across architectural boundaries.

Avoid:

```text
- cross-module direct aggregate access;
- shared classes knowing every module-specific type;
- framework types inside domain objects;
- persistence types inside application ports;
- response DTOs used as domain models;
- request DTOs passed deep into use cases.
```

## 11. Review checklist

For every new or changed class, ask:

```text
- What is this class responsible for?
- What would cause it to change?
- Does it mix transport, application, domain, and infrastructure concerns?
- Does it depend on a lower-level technical detail directly?
- Is the abstraction justified now?
- Could the name be more specific?
- Will adding the next similar case require editing this class again?
```

## 12. Anti-patterns

Avoid:

```text
- god services;
- generic helpers;
- fake interfaces for one class without boundary value;
- inheritance for code reuse when composition is clearer;
- abstract base classes with hidden workflow;
- static service locators;
- framework container usage inside business code;
- shared registries that know all module-specific classes;
- splitting every method into a class without a real responsibility.
```

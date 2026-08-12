# bitacora-de-obra
A WordPress theme. It includes a mini-app widget for taking notes and keeping detailed records of different tasks.

## Installation profiles

Bitácora separates its content model from the initial configuration of a site.

The core defines generic content structures such as notes, section items, sections,
and content classes. It does not require a fixed set of named sections.

A new installation may start with no section profile, or an explicit installation
profile may seed an initial set of sections and classes.

The original project configuration is represented by the `construccion` profile,
which currently defines:

- Documentos
- Materiales
- Catálogos
- Planos
- Paisajismo

This profile is not a universal default. Once installed, its sections become normal
WordPress data and may later be modified, hidden, archived, or extended independently
of the profile that created them.

Profiles contain initial data only; they must not introduce permanent behavioral
dependencies into the Bitácora core.

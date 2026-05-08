# 🧩 Papy3D Menu Visibility

Plugin WordPress avancé permettant de gérer la **visibilité des éléments de menu en fonction des rôles utilisateurs**.

Compatible WordPress moderne (6.5+), Full Site Editing (FSE) et conforme aux standards de sécurité **WordPress VIP 2026**.

![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue?logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4?logo=php)
![License](https://img.shields.io/badge/license-GPLv2-green)
![Standards](https://img.shields.io/badge/WP%20Coding%20Standards-VIP%202026-orange)
![Status](https://img.shields.io/badge/status-production%20ready-success)

---

## 🚀 Fonctionnalités

- 🎯 Contrôle de la visibilité de chaque élément de menu par rôle utilisateur
- 👁️ Deux modes de filtrage :
  - **Afficher** uniquement pour certains rôles
  - **Masquer** pour certains rôles
- 🧑‍💼 Support de tous les rôles WordPress natifs
- 🧱 Compatibilité menus classiques + Navigation Block (FSE)
- 🔐 Sécurité renforcée (nonce, sanitization, capabilities)
- 🌐 Compatible multisite
- ⚡ Léger, sans dépendance externe
- 🧩 Architecture OOP moderne (PHP 8.1+, strict types, namespace isolé)

---

## 🧱 Compatibilité

| Élément               | Support |
|-----------------------|---------|
| WordPress             | 6.5+    |
| PHP                   | 8.1+    |
| Menus classiques      | ✔       |
| Navigation Block (FSE)| ✔       |
| Multisite             | ✔       |

---

## ⚙️ Installation

1. Télécharger le plugin
2. Décompresser dans `/wp-content/plugins/papy3d-menu-visibility/`
3. Activer dans WordPress : **Extensions → Plugins installés → Activer**
4. Aller dans **Apparence → Menus**
5. Configurer la visibilité sur chaque élément de menu

---

## 🖼️ Interface admin

Dans **Apparence → Menus**, chaque élément dispose d'un panneau dédié :

```
┌─ Visibilité du menu ──────────────────────┐
│                                           │
│  (•) Afficher uniquement pour les rôles   │
│  ( ) Masquer pour les rôles               │
│                                           │
│  Rôles disponibles :                      │
│  [✔] Administrateur                       │
│  [✔] Éditeur                              │
│  [ ] Auteur                               │
│  [ ] Abonné                               │
│                                           │
└───────────────────────────────────────────┘
```

### Comportement par mode

| Mode | Aucun rôle coché | Rôles cochés |
|------|-----------------|--------------|
| **Afficher** | Élément visible par tous | Visible uniquement pour les rôles sélectionnés |
| **Masquer**  | Élément visible par tous | Masqué uniquement pour les rôles sélectionnés |

> **Note :** Si aucun rôle n'est coché, aucun filtrage n'est appliqué — l'élément se comporte normalement.

---

## 🧠 Fonctionnement

```
Élément de menu affiché
        │
        ▼
get_post_meta() → _p3dmv_roles + _p3dmv_mode
        │
        ├── Aucun rôle enregistré ?  →  Affichage normal
        │
        ├── mode = show  →  Affiché uniquement si l'utilisateur a un rôle correspondant
        │
        └── mode = hide  →  Affiché uniquement si l'utilisateur N'a PAS un rôle correspondant
```

Deux hooks WordPress sont utilisés :

| Hook | Usage |
|------|-------|
| `wp_nav_menu_objects` | Filtrage des menus classiques |
| `render_block_data`   | Filtrage des blocs `core/navigation-link` (FSE) |

---

## 🔐 Sécurité

- Vérification des **nonces** WordPress sur chaque sauvegarde
- **`wp_unslash()`** avant toute lecture de `$_POST`
- **`sanitize_key()`** sur tous les rôles et modes reçus
- Validation des rôles contre la liste WordPress réelle (`get_roles()`)
- Contrôle de capabilities (`edit_theme_options`)
- Aucune requête SQL directe
- Aucune dépendance externe

---

## 🏗️ Architecture technique

- Classe unique `Plugin` en namespace isolé `Papy3D\MenuVisibility`
- `declare(strict_types=1)` activé
- Hooks WordPress natifs uniquement
- Stockage via post meta sur le type `nav_menu_item`
- Classe déclarée `final` — pas d'héritage involontaire

---

## 🔌 Hooks utilisés

### Actions

| Hook | Rôle |
|------|------|
| `plugins_loaded` | Chargement des traductions |
| `admin_enqueue_scripts` | Styles admin (page `nav-menus.php`) |
| `wp_nav_menu_item_custom_fields` | Rendu du panneau de visibilité |
| `wp_update_nav_menu_item` | Sauvegarde des règles |

### Filters

| Hook | Rôle |
|------|------|
| `wp_nav_menu_objects` | Filtrage des éléments (menus classiques) |
| `render_block_data`   | Filtrage des blocs (FSE) |

---

## 📦 Structure du plugin

```
papy3d-menu-visibility/
├── papy3d-menu-visibility.php   ← Fichier principal (classe Plugin)
├── uninstall.php                ← Nettoyage à la désinstallation
├── assets/
│   └── admin.css                ← Styles du panneau admin
├── languages/                   ← Traductions (.po / .mo)
└── README.md
```

---

## 📦 Données stockées

| Clé meta        | Type     | Valeurs possibles                          |
|-----------------|----------|--------------------------------------------|
| `_p3dmv_roles`  | `array`  | Slugs de rôles WP, ex. `["editor","author"]` |
| `_p3dmv_mode`   | `string` | `show` ou `hide`                           |

Les clés sont préfixées `_p3dmv_` et stockées sur le post type `nav_menu_item`. Elles sont intégralement supprimées lors de la désinstallation.

---

## 🧹 Désinstallation

Lors de la suppression du plugin via WordPress :

- Suppression de toutes les métadonnées `_p3dmv_roles`
- Suppression de toutes les métadonnées `_p3dmv_mode`
- Support multisite : nettoyage sur chaque site du réseau

---

## ⭐ Pourquoi ce plugin ?

WordPress ne propose pas nativement un contrôle fin de la visibilité des menus par rôle.

Ce plugin permet de :

- Adapter l'interface selon le profil de l'utilisateur connecté
- Sécuriser les liens visibles sans modifier les permissions
- Améliorer l'expérience utilisateur sur les sites membres ou intranet
- Gérer des menus complexes sans multiplier les zones de navigation

---

## 🧑‍💻 Auteur

**Papy3D Factory**
[https://papy-3d-factory.xyz](https://papy-3d-factory.xyz)

---

## 📄 Licence

GPL v2 ou ultérieure — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

# Agent skill

The editable source is `skills/yii2-bootstrap6-widgets/SKILL.md` in this package. Its workflow covers Bootstrap 6
widget selection, configuration, migration, assets, and output encoding. Guides stay in `docs/`; API contracts stay
in PHPDoc and the generated API reference. The documentation site consumes these sources to generate its published
pages and skill distribution. Edit the package sources, not generated copies.

## Install in an application

### Automatic Composer installation

Complete the [one-time application setup](https://yii2-framework.github.io/docs/project/ai-agents/#composer-installation), enabling `llm/skills`
and automatic synchronization. Approve `yii2-extensions/bootstrap6` explicitly in `dependencies.composer.trusted`.
The installed package must include its skill bundle; its `extra.skills.source` declares the directory.

```bash
composer install
composer skills:show
```

Later dependency installs and updates synchronize the skill automatically; no per-skill installer is required.

### Optional npx installation

Run the pinned installer from the application root and select the clients you use:

```bash
npx skills@1.7.2 add yii2-extensions/bootstrap6 --skill yii2-bootstrap6-widgets
```

For a local checkout, pass its directory instead:

```bash
npx skills@1.7.2 add /path/to/bootstrap6 --skill yii2-bootstrap6-widgets
```

To inspect the source without installing, add `--list`. Omit `--global` for an application-specific installation.
Review the selected destination paths before proceeding; installing a Composer dependency in `vendor/` does not
make its skills automatically discoverable from the application root.

## Native discovery and activation

This package checkout includes links to the same canonical skill in `.agents/skills/` and `.claude/skills/`. They
provide native project discovery without separate editable copies:

| Client      | Project skill path                                                                         | Explicit activation                                |
| ----------- | ------------------------------------------------------------------------------------------ | -------------------------------------------------- |
| Codex       | `.agents/skills/yii2-bootstrap6-widgets/SKILL.md`                                          | `$yii2-bootstrap6-widgets` or the skill selector   |
| Claude Code | `.claude/skills/yii2-bootstrap6-widgets/SKILL.md`                                          | `/yii2-bootstrap6-widgets`                         |
| OpenCode    | `.opencode/skills/`, `.agents/skills/`, or `.claude/skills/`, followed by the skill folder | Native `skill` tool with `yii2-bootstrap6-widgets` |

OpenCode supports both shared paths; inspect its available skills and keep one effective entry if multiple aliases
produce duplicates. The installer can create a destination or alias for the selected client. For project paths and
client behavior, see [Codex skills](https://learn.chatgpt.com/docs/build-skills),
[Claude Code skills](https://code.claude.com/docs/en/skills), and [OpenCode skills](https://opencode.ai/docs/skills/).

Installation places files or links. Discovery exposes the skill's name and description. Activation loads its
instructions, either explicitly or when the client's selection matches the task. These are separate stages:
installation alone does not prove activation. After activation, the skill directs selective reading of the relevant
local guides, generated references, or published Markdown. A copied skill does not depend on `../../docs` existing.

To check setup, start a fresh client session at the application root, inspect the available skills, then invoke the
skill for one small widget task. Record discovery and activation separately from PHP lint, rendered output, and
browser behavior. For automatic selection, repeat with a natural task prompt that does not name the skill.

[Back to documentation](../README.md#documentation)

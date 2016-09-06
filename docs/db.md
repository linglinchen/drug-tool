# Tables

- [access_control_structure](tables/access_control_structure.md) - The structure of the ACl. Trees are supported.
- [access_controls](tables/access_controls.md) - Defines the rights of groups and users. User permissions take precedence over group permissions. All rights default to deny.
- [assignments](tables/assignments.md) - The users' work assignments, past and present.
- [atoms](tables/atoms.md) - These are roughly equivalent to monographs or pages.
- [boilerplates](tables/boilerplates.md) - XML templates for creating new atoms.
- [comments](tables/comments.md) - This is where discussion threads are stored. The table supports threads, but the feature isn't used by the current version of the software.
- [groups](tables/groups.md) - User groups. A user inherits the rights of the group that they belong to.
- **migrations** - A built-in Laravel table. Records which data migrations have been run. *Do not modify its data unless you know exactly what you're doing.*
- [molecules](tables/molecules.md) - Molecules are roughly equivalent to chapters.
- **password_resets** - A built-in Laravel table. Currently unused.
- [statuses](tables/statuses.md) - A lookup table for status codes.
- [tasks](tables/tasks.md) - A lookup table for task codes.
- **users** - Stores all user data except permissions. Every user should belong to a group.
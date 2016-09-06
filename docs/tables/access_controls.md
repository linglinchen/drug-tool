# access_controls

- **id**
- **user_id** - The user this permission belongs to. If **group_id** is set, then this should be null. User permissions take precedence over group permissions.
- **group_id** - The group this permission belongs to. If **user_id** is set, then this should be null.
- **access_control_structure_id** - The rule that this permission corresponds to.
- **permitted** - Can the group/user do the thing?
- **created_at**
- **updated_at**
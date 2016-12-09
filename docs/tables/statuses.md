# statuses

Each product should have its own set of statuses in this table. I recommend giving each product a block of 1000.

- **id**
- **title**
- **product_id**
- **active** - Is this status considered active when exporting? Statuses that represent a "trashed" state should have this field set to false.
- **publish** - When true, atoms in this status will be considered "golden" when exporting.
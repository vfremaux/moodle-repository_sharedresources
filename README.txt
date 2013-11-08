Sharedresource Repository
===============================

This component completes the sharedresource ensemble by allowing using sharedresource
in other contexts than simple publishing sharedresource entries with sharedresource
module activities. 

It provides a standard Repository upon the sharedresource pool so they can be browed
from a file_picker or a file browser GUI. When using file pickers or file browsers, 
the sharedresource record will be duplicated and relocalized to the calling context
(virtual copy), unless the caller has an explicit aliasing strategy. 

Status of developement : 
============================

This repository is yet in an intermediary state. We would like files to be presented
using metadata classification based browsing and not just file storage. Shared resources
are actually not organised into folder in the storage, but as flat folder (no phyisical
hierarchy) as organisatio hierarchy is usually provided by metadata (typical :
Node 9 : Classification in LOM model). There should be some provision to get one day
the Library search engine integrated in the repo so files could be searched here
agains same sorting criteria than in the Library front end.

Install
==============================

Shared resource module is the key part of a full indexed public resource center that 
will come as 4 complementary parts : 

- Shared resource module : master part 
- Shared resources block : Utilities to access to central library and make some resource conversions or feeding
- Shared resource repository  (this package) : A Repository view of the shared resource storage area, so shared resource can also be used and picked
as standard resource instances, or in other publication contexts
- Shared Resource Local Component : provides a front-end to librarian to search, browse and get some site level services around shared resources.
 
#Sentinel

`block_sentinel` provides an extendable means of denying access to Moodle courses based on any number of required checks.
The motivating use case is as follows: students are required to complete an orientation course before and complete any number of other 
steps before they gain access to their Moodle courses. On the other hand, excluding those students from the enrolment process would penalize 
instructors who ofter need to communicate with enrolled students through Moodle communication channels.

##Installation and Configuration
In order for it to have any effect, this block should be enabled site-wide. To do this,  

1. navigate to _Site Home_ as a user with administrative permissions.
2. turn editnig on.
3. add the block
4. configure the block, choosing to _Display throughout the entire site_


Admin settings give control over the behavior of the block.  

* `excluded_courses` - accepts a comma,separated list of courses that shold not be _patroled_ by the sentinel
* `landing_course`   - accepts a single course ID to which users will be redirected if they fail to pass all criteria
* `clients`          - accepts a comma-separated list of frankenstyle class names. These classes should reside in a file called `sentinel.php` located at the frankenstyle path. Additiionally, client classes should implement the `Sentinel` interface provided by `block_sentinel` in lib.php.
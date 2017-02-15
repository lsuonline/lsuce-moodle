# cas_help_links
Local Moodle plugin for displaying help links

## Usage
Currently, the front-facing method of the URL generator class will accept a course and return an array containing data about it's appropriate link. Here is an example of how it could be implemented in the "course overview" course list to construct an html link:

```
// Add CAS links
if (class_exists('local_cas_help_links_button_renderer')) {
    $html .= \local_cas_help_links_button_renderer::get_html_for_course($course, ['class' => 'btn cas_help']);
}
```
AND in the welcome area
```
// Add CAS links
if (class_exists('local_cas_help_links_button_renderer')) {
    $output .= \local_cas_help_links_button_renderer::get_html_for_user_id($user_id, ['class' => 'btn cas_edit_help']);
}
```

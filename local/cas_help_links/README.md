# cas_help_links
Local Moodle plugin for displaying help links

## Usage
Currently, the front-facing method of the URL generator class will accept a course and return an array containing data about it's appropriate link. Here is an example of how it could be implemented in the "course overview" course list to construct an html link:

```
if (class_exists('local_cas_help_links_url_generator')) {
    $help_url_array = \local_cas_help_links_url_generator::getUrlArrayForCourse($course);

    if ($help_url_array['display'])
        $html .= '<a class="btn cas_help" href="' . $help_url_array['url'] . '" target="_blank">' . $help_url_array['label'] . '</a>';
}
```

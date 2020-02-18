# CMB2-rest-query
Adds a query parameter to the REST API for any CMB2 field that has the property rest_query set on it.

## How to install
Option one
- Clone this plugin in to your wordpress plugin directory

Option two
- Download this plugin as a zip and add this zip as a plugin upload in your Wrodress admin area

In both cases, activate the plugin in wordpress

## How to use
### Setting up the metabox in CMB2
Add the query_rest property to the field that you wish to query by in your CMB2 field configuration.
- If set to true, the field ID will used to query the REST API.
- If set to a value, the value will be used to query REST API.

#### Example
```php
$cmb = new_cmb2_box( [
    'id'            => 'my_metabox',
    'title'         => __( 'My Metabox', 'cmb2' ),
    'object_types'  => [ 'post' ],
    'show_in_rest'  => WP_REST_Server::ALLMETHODS
] );

$cmb->add_field( [
    'id'            => 'my_text_field',
    'name'          => 'My text field',
    'type'          => 'text',
    'rest_query'    => true
] );

$cmb->add_field( [
    'id'            => 'custom_query_name',
    'name'          => 'My text field',
    'type'          => 'text',
    'rest_query'    => 'my_query'
] );
```

### Querying the REST API
For each field that has rest_query set, 3 parameters will be available in the REST request on each post type that this field's metabox is registered on:
- the field's rest_query value or id (as defined above)
- the above parameter appended with _compare
- the above parameter appended with _type

Adding the field's query parameter to the request will add that field to the request's meta query. By default compare will be '=' but using the {rest_query}_compare parameter, this can be changed to any option allowed by [WP_Meta_Query](https://developer.wordpress.org/reference/classes/wp_meta_query/). The type of the query parameter will be set to 'CHAR' by default but this can be overwritten using {rest_query}_type, again with the values allowd in WP_Meta_Query.

Following the above example a query could look like:
```javascript
const request = new Request('https://wordpress.org/wp-json/wp/v2/posts?my_text_field=hello&my_query=world&my_query_compare=LIKE');
```
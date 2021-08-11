<?php

namespace NotificationX\Core\Rest;

use NotificationX\Core\Database;
use NotificationX\Core\PostType;
use NotificationX\Core\REST;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\NotificationX;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class Posts extends WP_REST_Controller {
    /**
     * Instance of NotificationX
     *
     * @var NotificationX
     */
    use GetInstance;

    /**
     * Post type.
     *
     * @since 4.7.0
     * @var string
     */
    protected $post_type;

    /**
     * Constructor.
     *
     * @since 4.7.0
     *
     * @param string $post_type Post type.
     */
    public function __construct() {
        $this->namespace = 'notificationx/v1';
        $this->rest_base = 'nx';
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 4.7.0
     *
     * @see register_rest_route()
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    // 'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    // 'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ),
                // 'schema' => array($this, 'get_public_item_schema'),
            )
        );


        // $schema = $this->get_item_schema();
        $get_item_args = array(
            'context' => $this->get_context_param(array('default' => 'view')),
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Unique identifier for the object.', 'notificationx'),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    // 'args'                => $get_item_args,
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    // 'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                    'args'                => array(
                        'force' => array(
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => __('Whether to bypass Trash and force deletion.', 'notificationx'),
                        ),
                    ),
                ),
                // 'schema' => array($this, 'get_public_item_schema'),
            )
        );
    }

    /**
     * Checks if a given request has access to read posts.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request) {
        return true;
    }

    /**
     * Checks if a given request has access to read post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_item_permissions_check($request) {
        return true;
    }

    /**
     * Retrieves a collection of posts.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request) {
        $params     = $request->get_params();
        $status     = !empty($params['status']) ? $params['status'] : "all";
        $page       = !empty($params['page']) ? $params['page'] : 1;
        $per_page   = !empty($params['per_page']) ? $params['per_page'] : 20;
        $start_from = ($page - 1) * $per_page;
        $limit      = "ORDER BY a.updated_at DESC LIMIT $start_from, $per_page";
        $where      = [];

        if($status == 'enabled' || $status == 'disabled'){
            $where['enabled'] = $status == 'enabled' ? true : false;
        }
        $total_posts = Database::get_instance()->get_post(Database::$table_posts, [], 'count(*) AS total');
        $enabled     = Database::get_instance()->get_post(Database::$table_posts, ['enabled' => true], 'count(*) AS total');
        $disabled    = Database::get_instance()->get_post(Database::$table_posts, ['enabled' => false], 'count(*) AS total');

        return [
            'total'    => $total_posts['total'],
            'enabled'  => $enabled['total'],
            'disabled' => $disabled['total'],
            'posts'    => PostType::get_instance()->get_post_with_analytics($where, $limit),
        ];
    }

    /**
     * Retrieves a single post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_item($request) {
        return PostType::get_instance()->get_post($request['id']);
    }

    /**
     * Checks if a given request has access to create a post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
     */
    public function create_item_permissions_check($request) {
        if (!empty($request['id'])) {
            return new WP_Error(
                'rest_post_exists',
                __('Cannot create existing post.', 'notificationx'),
                array('status' => 400)
            );
        }

        return current_user_can('edit_posts');
    }

    /**
     * Creates a single post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function create_item($request) {
        if (!empty($request['nx_id'])) {
            return new WP_Error(
                'rest_post_exists',
                __('Cannot create existing post.', 'notificationx'),
                array('status' => 400)
            );
        }

        // $prepared_post = $this->prepare_item_for_database($request);

        // if (is_wp_error($prepared_post)) {
        //     return $prepared_post;
        // }

        $params = $request->get_params();
        return PostType::get_instance()->save_post($params);
    }

    /**
     * Checks if a given request has access to update a post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
     */
    public function update_item_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    /**
     * Updates a single post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function update_item($request) {
        $params = $request->get_params();
        return PostType::get_instance()->save_post($params);
    }

    /**
     * Checks if a given request has access to delete a post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
     */
    public function delete_item_permissions_check($request) {
        // if ($post && !$this->check_delete_permission($post)) {
        //     return new WP_Error(
        //         'rest_cannot_delete',
        //         __('Sorry, you are not allowed to delete this post.'),
        //         array('status' => rest_authorization_required_code())
        //     );
        // }
        return current_user_can('edit_posts');
    }

    /**
     * Deletes a single post.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function delete_item($request) {
        if(PostType::get_instance()->delete_post($request['id'])){
            wp_send_json_success();
        }
        wp_send_json_error();
    }






    /**
     * Retrieves the post's schema, conforming to JSON Schema.
     *
     * @since 4.7.0
     *
     * @return array Item schema data.
     */
    public function get_item_schema() {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }

        $schema = array(
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'notificationx',
            'type' => 'object',
            // Base properties for every Post.
            'properties' => array(
                'nx_id' => array(
                    'description' => __('Unique identifier for the Notification.', 'notificationx'),
                    'type' => 'integer',
                    'context' => array('view', 'edit'),
                    'readonly' => true,
                ),
                'title' => array(
                    'description' => __('Title of Notification.', 'notificationx'),
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
                'type' => array(
                    'description' => __('Type of Notification.', 'notificationx'),
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
                'source' => array(
                    'description' => __('Selected Source of Notification.', 'notificationx'),
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
                'theme' => array(
                    'description' => __('Selected Theme.', 'notificationx'),
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
                'created_at' => array(
                    'description' => __('The date the Notification was created.', 'notificationx'),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array('view', 'edit'),
                ),
                'updated_at' => array(
                    'description' => __('The date the Notification was updated.', 'notificationx'),
                    'type'        => 'string',
                    'format'      => 'date-time',
                    'context'     => array('view', 'edit'),
                ),
                'enabled' => array(
                    'description' => __('Whether the Notification is enabled.', 'notificationx'),
                    'type' => 'boolean',
                    'context' => array('view', 'edit'),
                ),
                'global_queue' => array(
                    'description' => __('Whether current Notification is in global queue.', 'notificationx'),
                    'type' => 'boolean',
                    'context' => array('view', 'edit'),
                ),
            ),
        );

        $tabs = GlobalFields::get_instance()->tabs();
        $fields = NotificationX::get_instance()->get_field_names($tabs['tabs']);

        $schema['properties'] = $this->create_schema($fields, $schema['properties']);
        $this->schema = $schema;
        return $this->add_additional_fields_schema($this->schema);
    }

    public function create_schema($fields, $schema = []) {
        foreach ($fields as $key => $field) {
            if (is_array($field) && !isset($field['type'])) {
                $_field = reset($field);
                $schema[$key] = array(
                    'description' => isset($_field['parent_label']) ? $_field['parent_label'] : '',
                    'type'        => 'object',
                    'properties'  => $this->create_schema($field)
                );
            } else {
                $schema[$key] = array(
                    'description' => $field['help'],
                    'type'        => $this->get_type($field['type']),
                    'context'     => array('view', 'edit'),
                    'default'     => $field['default'],
                );
            }
        }

        return $schema;
    }

    public function get_type($type) {
        switch ($type) {
            case "integer":
            case "number":
                return "number";
            case "string":
            case "radio-card":
            case "message":
            case "colorpicker":
            case "select":
            case "text":
            case "media":
                return "string";
            case "boolean":
            case "checkbox":
            case "toggle":
            case "slider":
                return "boolean";
            default:
                return "string";
                break;
        }
    }






    /**
     * Determines the allowed query_vars for a get_items() response and prepares
     * them for WP_Query.
     *
     * @since 4.7.0
     *
     * @param array $prepared_args Optional. Prepared WP_Query arguments. Default empty array.
     * @param WP_REST_Request $request Optional. Full details about the request.
     * @return array Items query arguments.
     */
    protected function prepare_items_query($prepared_args = array(), $request = null) {
        $query_args = array();

        foreach ($prepared_args as $key => $value) {
            /**
             * Filters the query_vars used in get_items() for the constructed query.
             *
             * The dynamic portion of the hook name, `$key`, refers to the query_var key.
             *
             * @since 4.7.0
             *
             * @param string $value The query_var value.
             */
            $query_args[$key] = apply_filters("rest_query_var-{$key}", $value); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        }

        if ('post' !== $this->post_type || !isset($query_args['ignore_sticky_posts'])) {
            $query_args['ignore_sticky_posts'] = true;
        }

        // Map to proper WP_Query orderby param.
        if (isset($query_args['orderby']) && isset($request['orderby'])) {
            $orderby_mappings = array(
                'id' => 'ID',
                'include' => 'post__in',
                'slug' => 'post_name',
                'include_slugs' => 'post_name__in',
            );

            if (isset($orderby_mappings[$request['orderby']])) {
                $query_args['orderby'] = $orderby_mappings[$request['orderby']];
            }
        }

        return $query_args;
    }

    /**
     * Checks the post_date_gmt or modified_gmt and prepare any post or
     * modified date for single post output.
     *
     * @since 4.7.0
     *
     * @param string $date_gmt GMT publication time.
     * @param string|null $date Optional. Local publication time. Default null.
     * @return string|null ISO8601/RFC3339 formatted datetime.
     */
    protected function prepare_date_response($date_gmt, $date = null) {
        // Use the date if passed.
        if (isset($date)) {
            return mysql_to_rfc3339($date);
        }

        // Return null if $date_gmt is empty/zeros.
        if ('0000-00-00 00:00:00' === $date_gmt) {
            return null;
        }

        // Return the formatted datetime.
        return mysql_to_rfc3339($date_gmt);
    }

    /**
     * Prepares a single post for create or update.
     *
     * @since 4.7.0
     *
     * @param WP_REST_Request $request Request object.
     * @return stdClass|WP_Error Post object or WP_Error.
     */
    protected function prepare_item_for_database($request) {
        $prepared_post = new stdClass();
        $current_status = '';

        // Post ID.
        if (isset($request['id'])) {
            $existing_post = $this->get_post($request['id']);
            if (is_wp_error($existing_post)) {
                return $existing_post;
            }

            $prepared_post->ID = $existing_post->ID;
            $current_status = $existing_post->post_status;
        }

        $schema = $this->get_item_schema();

        // Post title.
        if (!empty($schema['properties']['title']) && isset($request['title'])) {
            if (is_string($request['title'])) {
                $prepared_post->post_title = $request['title'];
            } elseif (!empty($request['title']['raw'])) {
                $prepared_post->post_title = $request['title']['raw'];
            }
        }

        // Post content.
        if (!empty($schema['properties']['content']) && isset($request['content'])) {
            if (is_string($request['content'])) {
                $prepared_post->post_content = $request['content'];
            } elseif (isset($request['content']['raw'])) {
                $prepared_post->post_content = $request['content']['raw'];
            }
        }

        // Post excerpt.
        if (!empty($schema['properties']['excerpt']) && isset($request['excerpt'])) {
            if (is_string($request['excerpt'])) {
                $prepared_post->post_excerpt = $request['excerpt'];
            } elseif (isset($request['excerpt']['raw'])) {
                $prepared_post->post_excerpt = $request['excerpt']['raw'];
            }
        }

        // Post type.
        if (empty($request['id'])) {
            // Creating new post, use default type for the controller.
            $prepared_post->post_type = $this->post_type;
        } else {
            // Updating a post, use previous type.
            $prepared_post->post_type = get_post_type($request['id']);
        }

        $post_type = get_post_type_object($prepared_post->post_type);

        // Post status.
        if (
            !empty($schema['properties']['status']) &&
            isset($request['status']) &&
            (!$current_status || $current_status !== $request['status'])
        ) {
            $status = $this->handle_status_param($request['status'], $post_type);

            if (is_wp_error($status)) {
                return $status;
            }

            $prepared_post->post_status = $status;
        }

        // Post date.
        if (!empty($schema['properties']['date']) && !empty($request['date'])) {
            $current_date = isset($prepared_post->ID) ? get_post($prepared_post->ID)->post_date : false;
            $date_data = rest_get_date_with_gmt($request['date']);

            if (!empty($date_data) && $current_date !== $date_data[0]) {
                list($prepared_post->post_date, $prepared_post->post_date_gmt) = $date_data;
                $prepared_post->edit_date = true;
            }
        } elseif (!empty($schema['properties']['date_gmt']) && !empty($request['date_gmt'])) {
            $current_date = isset($prepared_post->ID) ? get_post($prepared_post->ID)->post_date_gmt : false;
            $date_data = rest_get_date_with_gmt($request['date_gmt'], true);

            if (!empty($date_data) && $current_date !== $date_data[1]) {
                list($prepared_post->post_date, $prepared_post->post_date_gmt) = $date_data;
                $prepared_post->edit_date = true;
            }
        }

        // Sending a null date or date_gmt value resets date and date_gmt to their
        // default values (`0000-00-00 00:00:00`).
        if (
            (!empty($schema['properties']['date_gmt']) && $request->has_param('date_gmt') && null === $request['date_gmt']) ||
            (!empty($schema['properties']['date']) && $request->has_param('date') && null === $request['date'])
        ) {
            $prepared_post->post_date_gmt = null;
            $prepared_post->post_date = null;
        }

        // Post slug.
        if (!empty($schema['properties']['slug']) && isset($request['slug'])) {
            $prepared_post->post_name = $request['slug'];
        }

        // Author.
        if (!empty($schema['properties']['author']) && !empty($request['author'])) {
            $post_author = (int) $request['author'];

            if (get_current_user_id() !== $post_author) {
                $user_obj = get_userdata($post_author);

                if (!$user_obj) {
                    return new WP_Error(
                        'rest_invalid_author',
                        __('Invalid author ID.', 'notificationx'),
                        array('status' => 400)
                    );
                }
            }

            $prepared_post->post_author = $post_author;
        }

        // Post password.
        if (!empty($schema['properties']['password']) && isset($request['password'])) {
            $prepared_post->post_password = $request['password'];

            if ('' !== $request['password']) {
                if (!empty($schema['properties']['sticky']) && !empty($request['sticky'])) {
                    return new WP_Error(
                        'rest_invalid_field',
                        __('A post can not be sticky and have a password.', 'notificationx'),
                        array('status' => 400)
                    );
                }

                if (!empty($prepared_post->ID) && is_sticky($prepared_post->ID)) {
                    return new WP_Error(
                        'rest_invalid_field',
                        __('A sticky post can not be password protected.', 'notificationx'),
                        array('status' => 400)
                    );
                }
            }
        }

        if (!empty($schema['properties']['sticky']) && !empty($request['sticky'])) {
            if (!empty($prepared_post->ID) && post_password_required($prepared_post->ID)) {
                return new WP_Error(
                    'rest_invalid_field',
                    __('A password protected post can not be set to sticky.', 'notificationx'),
                    array('status' => 400)
                );
            }
        }

        // Parent.
        if (!empty($schema['properties']['parent']) && isset($request['parent'])) {
            if (0 === (int) $request['parent']) {
                $prepared_post->post_parent = 0;
            } else {
                $parent = get_post((int) $request['parent']);

                if (empty($parent)) {
                    return new WP_Error(
                        'rest_post_invalid_id',
                        __('Invalid post parent ID.', 'notificationx'),
                        array('status' => 400)
                    );
                }

                $prepared_post->post_parent = (int) $parent->ID;
            }
        }

        // Menu order.
        if (!empty($schema['properties']['menu_order']) && isset($request['menu_order'])) {
            $prepared_post->menu_order = (int) $request['menu_order'];
        }

        // Comment status.
        if (!empty($schema['properties']['comment_status']) && !empty($request['comment_status'])) {
            $prepared_post->comment_status = $request['comment_status'];
        }

        // Ping status.
        if (!empty($schema['properties']['ping_status']) && !empty($request['ping_status'])) {
            $prepared_post->ping_status = $request['ping_status'];
        }

        if (!empty($schema['properties']['template'])) {
            // Force template to null so that it can be handled exclusively by the REST controller.
            $prepared_post->page_template = null;
        }

        /**
         * Filters a post before it is inserted via the REST API.
         *
         * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
         *
         * @since 4.7.0
         *
         * @param stdClass $prepared_post An object representing a single post prepared
         * for inserting or updating the database.
         * @param WP_REST_Request $request Request object.
         */
        return apply_filters("rest_pre_insert_{$this->post_type}", $prepared_post, $request);
    }

    /**
     * Checks whether the status is valid for the given post.
     *
     * Allows for sending an update request with the current status, even if that status would not be acceptable.
     *
     * @since 5.6.0
     *
     * @param string $status The provided status.
     * @param WP_REST_Request $request The request object.
     * @param string $param The parameter name.
     * @return true|WP_Error True if the status is valid, or WP_Error if not.
     */
    public function check_status($status, $request, $param) {
        if ($request['id']) {
            $post = $this->get_post($request['id']);

            if (!is_wp_error($post) && $post->post_status === $status) {
                return true;
            }
        }

        $args = $request->get_attributes()['args'][$param];

        return rest_validate_value_from_schema($status, $args, $param);
    }

    /**
     * Determines validity and normalizes the given status parameter.
     *
     * @since 4.7.0
     *
     * @param string $post_status Post status.
     * @param WP_Post_Type $post_type Post type.
     * @return string|WP_Error Post status or WP_Error if lacking the proper permission.
     */
    protected function handle_status_param($post_status, $post_type) {

        switch ($post_status) {
            case 'draft':
            case 'pending':
                break;
            case 'private':
                if (!current_user_can($post_type->cap->publish_posts)) {
                    return new WP_Error(
                        'rest_cannot_publish',
                        __('Sorry, you are not allowed to create private posts in this post type.', 'notificationx'),
                        array('status' => rest_authorization_required_code())
                    );
                }
                break;
            case 'publish':
            case 'future':
                if (!current_user_can($post_type->cap->publish_posts)) {
                    return new WP_Error(
                        'rest_cannot_publish',
                        __('Sorry, you are not allowed to publish posts in this post type.', 'notificationx'),
                        array('status' => rest_authorization_required_code())
                    );
                }
                break;
            default:
                if (!get_post_status_object($post_status)) {
                    $post_status = 'draft';
                }
                break;
        }

        return $post_status;
    }

    /**
     * Determines the featured media based on a request param.
     *
     * @since 4.7.0
     *
     * @param int $featured_media Featured Media ID.
     * @param int $post_id Post ID.
     * @return bool|WP_Error Whether the post thumbnail was successfully deleted, otherwise WP_Error.
     */
    protected function handle_featured_media($featured_media, $post_id) {

        $featured_media = (int) $featured_media;
        if ($featured_media) {
            $result = set_post_thumbnail($post_id, $featured_media);
            if ($result) {
                return true;
            } else {
                return new WP_Error(
                    'rest_invalid_featured_media',
                    __('Invalid featured media ID.', 'notificationx'),
                    array('status' => 400)
                );
            }
        } else {
            return delete_post_thumbnail($post_id);
        }
    }

    /**
     * Check whether the template is valid for the given post.
     *
     * @since 4.9.0
     *
     * @param string $template Page template filename.
     * @param WP_REST_Request $request Request.
     * @return bool|WP_Error True if template is still valid or if the same as existing value, or false if template not supported.
     */
    public function check_template($template, $request) {

        if (!$template) {
            return true;
        }

        if ($request['id']) {
            $post = get_post($request['id']);
            $current_template = get_page_template_slug($request['id']);
        } else {
            $post = null;
            $current_template = '';
        }

        // Always allow for updating a post to the same template, even if that template is no longer supported.
        if ($template === $current_template) {
            return true;
        }

        // If this is a create request, get_post() will return null and wp theme will fallback to the passed post type.
        $allowed_templates = wp_get_theme()->get_page_templates($post, $this->post_type);

        if (isset($allowed_templates[$template])) {
            return true;
        }

        return new WP_Error(
            'rest_invalid_param',
            /* translators: 1: Parameter, 2: List of valid values. */
            sprintf(__('%1$s is not one of %2$s.', 'notificationx'), 'template', implode(', ', array_keys($allowed_templates)))
        );
    }

    /**
     * Sets the template for a post.
     *
     * @since 4.7.0
     * @since 4.9.0 Added the `$validate` parameter.
     *
     * @param string $template Page template filename.
     * @param int $post_id Post ID.
     * @param bool $validate Whether to validate that the template selected is valid.
     */
    public function handle_template($template, $post_id, $validate = false) {

        if ($validate && !array_key_exists($template, wp_get_theme()->get_page_templates(get_post($post_id)))) {
            $template = '';
        }

        update_post_meta($post_id, '_wp_page_template', $template);
    }

    /**
     * Checks if a given post type can be viewed or managed.
     *
     * @since 4.7.0
     *
     * @param WP_Post_Type|string $post_type Post type name or object.
     * @return bool Whether the post type is allowed in REST.
     */
    protected function check_is_post_type_allowed($post_type) {
        if (!is_object($post_type)) {
            $post_type = get_post_type_object($post_type);
        }

        if (!empty($post_type) && !empty($post_type->show_in_rest)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a post can be read.
     *
     * Correctly handles posts with the inherit status.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return bool Whether the post can be read.
     */
    public function check_read_permission($post) {
        $post_type = get_post_type_object($post->post_type);
        if (!$this->check_is_post_type_allowed($post_type)) {
            return false;
        }

        // Is the post readable?
        if ('publish' === $post->post_status || current_user_can('read_post', $post->ID)) {
            return true;
        }

        $post_status_obj = get_post_status_object($post->post_status);
        if ($post_status_obj && $post_status_obj->public) {
            return true;
        }

        // Can we read the parent if we're inheriting?
        if ('inherit' === $post->post_status && $post->post_parent > 0) {
            $parent = get_post($post->post_parent);
            if ($parent) {
                return $this->check_read_permission($parent);
            }
        }

        /*
            * If there isn't a parent, but the status is set to inherit, assume
            * it's published (as per get_post_status()).
            */
        if ('inherit' === $post->post_status) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a post can be edited.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return bool Whether the post can be edited.
     */
    protected function check_update_permission($post) {
        $post_type = get_post_type_object($post->post_type);

        if (!$this->check_is_post_type_allowed($post_type)) {
            return false;
        }

        return current_user_can('edit_post', $post->ID);
    }

    /**
     * Checks if a post can be created.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return bool Whether the post can be created.
     */
    protected function check_create_permission($post) {
        $post_type = get_post_type_object($post->post_type);

        if (!$this->check_is_post_type_allowed($post_type)) {
            return false;
        }

        return current_user_can($post_type->cap->create_posts);
    }

    /**
     * Checks if a post can be deleted.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return bool Whether the post can be deleted.
     */
    protected function check_delete_permission($post) {
        $post_type = get_post_type_object($post->post_type);

        if (!$this->check_is_post_type_allowed($post_type)) {
            return false;
        }

        return current_user_can('delete_post', $post->ID);
    }

    /**
     * Prepares a single post output for response.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function prepare_item_for_response($post, $request) {
        $GLOBALS['post'] = $post;

        setup_postdata($post);

        $fields = $this->get_fields_for_response($request);

        // Base fields for every post.
        $data = array();

        if (rest_is_field_included('id', $fields)) {
            $data['id'] = $post->ID;
        }

        if (rest_is_field_included('date', $fields)) {
            $data['date'] = $this->prepare_date_response($post->post_date_gmt, $post->post_date);
        }

        if (rest_is_field_included('date_gmt', $fields)) {
            /*
            * For drafts, `post_date_gmt` may not be set, indicating that the date
            * of the draft should be updated each time it is saved (see #38883).
            * In this case, shim the value based on the `post_date` field
            * with the site's timezone offset applied.
            */
            if ('0000-00-00 00:00:00' === $post->post_date_gmt) {
                $post_date_gmt = get_gmt_from_date($post->post_date);
            } else {
                $post_date_gmt = $post->post_date_gmt;
            }
            $data['date_gmt'] = $this->prepare_date_response($post_date_gmt);
        }

        if (rest_is_field_included('guid', $fields)) {
            $data['guid'] = array(
                /** This filter is documented in wp-includes/post-template.php */
                'rendered' => apply_filters('get_the_guid', $post->guid, $post->ID),
                'raw' => $post->guid,
            );
        }

        if (rest_is_field_included('modified', $fields)) {
            $data['modified'] = $this->prepare_date_response($post->post_modified_gmt, $post->post_modified);
        }

        if (rest_is_field_included('modified_gmt', $fields)) {
            /*
            * For drafts, `post_modified_gmt` may not be set (see `post_date_gmt` comments
            * above). In this case, shim the value based on the `post_modified` field
            * with the site's timezone offset applied.
            */
            if ('0000-00-00 00:00:00' === $post->post_modified_gmt) {
                $post_modified_gmt = gmdate('Y-m-d H:i:s', strtotime($post->post_modified) - (get_option('gmt_offset') * 3600));
            } else {
                $post_modified_gmt = $post->post_modified_gmt;
            }
            $data['modified_gmt'] = $this->prepare_date_response($post_modified_gmt);
        }

        if (rest_is_field_included('password', $fields)) {
            $data['password'] = $post->post_password;
        }

        if (rest_is_field_included('slug', $fields)) {
            $data['slug'] = $post->post_name;
        }

        if (rest_is_field_included('status', $fields)) {
            $data['status'] = $post->post_status;
        }

        if (rest_is_field_included('type', $fields)) {
            $data['type'] = $post->post_type;
        }

        if (rest_is_field_included('link', $fields)) {
            $data['link'] = get_permalink($post->ID);
        }

        if (rest_is_field_included('title', $fields)) {
            $data['title'] = array();
        }
        if (rest_is_field_included('title.raw', $fields)) {
            $data['title']['raw'] = $post->post_title;
        }
        if (rest_is_field_included('title.rendered', $fields)) {
            add_filter('protected_title_format', array($this, 'protected_title_format'));

            $data['title']['rendered'] = get_the_title($post->ID);

            remove_filter('protected_title_format', array($this, 'protected_title_format'));
        }

        $has_password_filter = false;

        if ($this->can_access_password_content($post, $request)) {
            $this->password_check_passed[$post->ID] = true;
            // Allow access to the post, permissions already checked before.
            add_filter('post_password_required', array($this, 'check_password_required'), 10, 2);

            $has_password_filter = true;
        }

        if (rest_is_field_included('content', $fields)) {
            $data['content'] = array();
        }
        if (rest_is_field_included('content.raw', $fields)) {
            $data['content']['raw'] = $post->post_content;
        }
        if (rest_is_field_included('content.rendered', $fields)) {
            /** This filter is documented in wp-includes/post-template.php */
            $data['content']['rendered'] = post_password_required($post) ? '' : apply_filters('the_content', $post->post_content);
        }
        if (rest_is_field_included('content.protected', $fields)) {
            $data['content']['protected'] = (bool) $post->post_password;
        }
        if (rest_is_field_included('content.block_version', $fields)) {
            $data['content']['block_version'] = block_version($post->post_content);
        }

        if (rest_is_field_included('excerpt', $fields)) {
            /** This filter is documented in wp-includes/post-template.php */
            $excerpt = apply_filters('get_the_excerpt', $post->post_excerpt, $post);

            /** This filter is documented in wp-includes/post-template.php */
            $excerpt = apply_filters('the_excerpt', $excerpt);

            $data['excerpt'] = array(
                'raw' => $post->post_excerpt,
                'rendered' => post_password_required($post) ? '' : $excerpt,
                'protected' => (bool) $post->post_password,
            );
        }

        if ($has_password_filter) {
            // Reset filter.
            remove_filter('post_password_required', array($this, 'check_password_required'));
        }

        if (rest_is_field_included('author', $fields)) {
            $data['author'] = (int) $post->post_author;
        }

        if (rest_is_field_included('featured_media', $fields)) {
            $data['featured_media'] = (int) get_post_thumbnail_id($post->ID);
        }

        if (rest_is_field_included('parent', $fields)) {
            $data['parent'] = (int) $post->post_parent;
        }

        if (rest_is_field_included('menu_order', $fields)) {
            $data['menu_order'] = (int) $post->menu_order;
        }

        if (rest_is_field_included('comment_status', $fields)) {
            $data['comment_status'] = $post->comment_status;
        }

        if (rest_is_field_included('ping_status', $fields)) {
            $data['ping_status'] = $post->ping_status;
        }

        if (rest_is_field_included('sticky', $fields)) {
            $data['sticky'] = is_sticky($post->ID);
        }

        if (rest_is_field_included('template', $fields)) {
            $template = get_page_template_slug($post->ID);
            if ($template) {
                $data['template'] = $template;
            } else {
                $data['template'] = '';
            }
        }

        if (rest_is_field_included('format', $fields)) {
            $data['format'] = get_post_format($post->ID);

            // Fill in blank post format.
            if (empty($data['format'])) {
                $data['format'] = 'standard';
            }
        }

        if (rest_is_field_included('meta', $fields)) {
            $data['meta'] = $this->meta->get_value($post->ID, $request);
        }

        $taxonomies = wp_list_filter(get_object_taxonomies($this->post_type, 'objects'), array('show_in_rest' => true));

        foreach ($taxonomies as $taxonomy) {
            $base = !empty($taxonomy->rest_base) ? $taxonomy->rest_base : $taxonomy->name;

            if (rest_is_field_included($base, $fields)) {
                $terms = get_the_terms($post, $taxonomy->name);
                $data[$base] = $terms ? array_values(wp_list_pluck($terms, 'term_id')) : array();
            }
        }

        $post_type_obj = get_post_type_object($post->post_type);
        if (is_post_type_viewable($post_type_obj) && $post_type_obj->public) {
            $permalink_template_requested = rest_is_field_included('permalink_template', $fields);
            $generated_slug_requested = rest_is_field_included('generated_slug', $fields);

            if ($permalink_template_requested || $generated_slug_requested) {
                if (!function_exists('get_sample_permalink')) {
                    require_once ABSPATH . 'wp-admin/includes/post.php';
                }

                $sample_permalink = get_sample_permalink($post->ID, $post->post_title, '');

                if ($permalink_template_requested) {
                    $data['permalink_template'] = $sample_permalink[0];
                }

                if ($generated_slug_requested) {
                    $data['generated_slug'] = $sample_permalink[1];
                }
            }
        }

        $context = !empty($request['context']) ? $request['context'] : 'view';
        $data = $this->add_additional_fields_to_object($data, $request);
        $data = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        $links = $this->prepare_links($post);
        $response->add_links($links);

        if (!empty($links['self']['href'])) {
            $actions = $this->get_available_actions($post, $request);

            $self = $links['self']['href'];

            foreach ($actions as $rel) {
                $response->add_link($rel, $self);
            }
        }

        /**
         * Filters the post data for a REST API response.
         *
         * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
         *
         * Possible filter names include:
         *
         * - `rest_prepare_post`
         * - `rest_prepare_page`
         * - `rest_prepare_attachment`
         *
         * @since 4.7.0
         *
         * @param WP_REST_Response $response The response object.
         * @param WP_Post $post Post object.
         * @param WP_REST_Request $request Request object.
         */
        return apply_filters("rest_prepare_{$this->post_type}", $response, $post, $request);
    }

    /**
     * Overwrites the default protected title format.
     *
     * By default, WordPress will show password protected posts with a title of
     * "Protected: %s", as the REST API communicates the protected status of a post
     * in a machine readable format, we remove the "Protected: " prefix.
     *
     * @since 4.7.0
     *
     * @return string Protected title format.
     */
    public function protected_title_format() {
        return '%s';
    }

    /**
     * Prepares links for the request.
     *
     * @since 4.7.0
     *
     * @param WP_Post $post Post object.
     * @return array Links for the given post.
     */
    protected function prepare_links($post) {
        $base = sprintf('%s/%s', $this->namespace, $this->rest_base);

        // Entity meta.
        $links = array(
            'self' => array(
                'href' => rest_url(trailingslashit($base) . $post->ID),
            ),
            'collection' => array(
                'href' => rest_url($base),
            ),
            'about' => array(
                'href' => rest_url('wp/v2/types/' . $this->post_type),
            ),
        );

        if ((in_array($post->post_type, array('post', 'page'), true) || post_type_supports($post->post_type, 'author'))
            && !empty($post->post_author)
        ) {
            $links['author'] = array(
                'href' => rest_url('wp/v2/users/' . $post->post_author),
                'embeddable' => true,
            );
        }

        if (in_array($post->post_type, array('post', 'page'), true) || post_type_supports($post->post_type, 'comments')) {
            $replies_url = rest_url('wp/v2/comments');
            $replies_url = add_query_arg('post', $post->ID, $replies_url);

            $links['replies'] = array(
                'href' => $replies_url,
                'embeddable' => true,
            );
        }

        if (in_array($post->post_type, array('post', 'page'), true) || post_type_supports($post->post_type, 'revisions')) {
            $revisions = wp_get_post_revisions($post->ID, array('fields' => 'ids'));
            $revisions_count = count($revisions);

            $links['version-history'] = array(
                'href' => rest_url(trailingslashit($base) . $post->ID . '/revisions'),
                'count' => $revisions_count,
            );

            if ($revisions_count > 0) {
                $last_revision = array_shift($revisions);

                $links['predecessor-version'] = array(
                    'href' => rest_url(trailingslashit($base) . $post->ID . '/revisions/' . $last_revision),
                    'id' => $last_revision,
                );
            }
        }

        $post_type_obj = get_post_type_object($post->post_type);

        if ($post_type_obj->hierarchical && !empty($post->post_parent)) {
            $links['up'] = array(
                'href' => rest_url(trailingslashit($base) . (int) $post->post_parent),
                'embeddable' => true,
            );
        }

        // If we have a featured media, add that.
        $featured_media = get_post_thumbnail_id($post->ID);
        if ($featured_media) {
            $image_url = rest_url('wp/v2/media/' . $featured_media);

            $links['https://api.w.org/featuredmedia'] = array(
                'href' => $image_url,
                'embeddable' => true,
            );
        }

        if (!in_array($post->post_type, array('attachment', 'nav_menu_item', 'revision'), true)) {
            $attachments_url = rest_url('wp/v2/media');
            $attachments_url = add_query_arg('parent', $post->ID, $attachments_url);

            $links['https://api.w.org/attachment'] = array(
                'href' => $attachments_url,
            );
        }

        $taxonomies = get_object_taxonomies($post->post_type);

        if (!empty($taxonomies)) {
            $links['https://api.w.org/term'] = array();

            foreach ($taxonomies as $tax) {
                $taxonomy_obj = get_taxonomy($tax);

                // Skip taxonomies that are not public.
                if (empty($taxonomy_obj->show_in_rest)) {
                    continue;
                }

                $tax_base = !empty($taxonomy_obj->rest_base) ? $taxonomy_obj->rest_base : $tax;

                $terms_url = add_query_arg(
                    'post',
                    $post->ID,
                    rest_url('wp/v2/' . $tax_base)
                );

                $links['https://api.w.org/term'][] = array(
                    'href' => $terms_url,
                    'taxonomy' => $tax,
                    'embeddable' => true,
                );
            }
        }

        return $links;
    }

    /**
     * Get the link relations available for the post and current user.
     *
     * @since 4.9.8
     *
     * @param WP_Post $post Post object.
     * @param WP_REST_Request $request Request object.
     * @return array List of link relations.
     */
    protected function get_available_actions($post, $request) {

        if ('edit' !== $request['context']) {
            return array();
        }

        $rels = array();

        $post_type = get_post_type_object($post->post_type);

        if ('attachment' !== $this->post_type && current_user_can($post_type->cap->publish_posts)) {
            $rels[] = 'https://api.w.org/action-publish';
        }

        if (current_user_can('unfiltered_html')) {
            $rels[] = 'https://api.w.org/action-unfiltered-html';
        }

        if ('post' === $post_type->name) {
            if (current_user_can($post_type->cap->edit_others_posts) && current_user_can($post_type->cap->publish_posts)) {
                $rels[] = 'https://api.w.org/action-sticky';
            }
        }

        if (post_type_supports($post_type->name, 'author')) {
            if (current_user_can($post_type->cap->edit_others_posts)) {
                $rels[] = 'https://api.w.org/action-assign-author';
            }
        }

        $taxonomies = wp_list_filter(get_object_taxonomies($this->post_type, 'objects'), array('show_in_rest' => true));

        foreach ($taxonomies as $tax) {
            $tax_base = !empty($tax->rest_base) ? $tax->rest_base : $tax->name;
            $create_cap = is_taxonomy_hierarchical($tax->name) ? $tax->cap->edit_terms : $tax->cap->assign_terms;

            if (current_user_can($create_cap)) {
                $rels[] = 'https://api.w.org/action-create-' . $tax_base;
            }

            if (current_user_can($tax->cap->assign_terms)) {
                $rels[] = 'https://api.w.org/action-assign-' . $tax_base;
            }
        }

        return $rels;
    }

    /**
     * Retrieve Link Description Objects that should be added to the Schema for the posts collection.
     *
     * @since 4.9.8
     *
     * @return array
     */
    protected function get_schema_links() {

        $href = rest_url("{$this->namespace}/{$this->rest_base}/{id}");

        $links = array();

        if ('attachment' !== $this->post_type) {
            $links[] = array(
                'rel' => 'https://api.w.org/action-publish',
                'title' => __('The current user can publish this post.', 'notificationx'),
                'href' => $href,
                'targetSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'status' => array(
                            'type' => 'string',
                            'enum' => array('publish', 'future'),
                        ),
                    ),
                ),
            );
        }

        $links[] = array(
            'rel' => 'https://api.w.org/action-unfiltered-html',
            'title' => __('The current user can post unfiltered HTML markup and JavaScript.', 'notificationx'),
            'href' => $href,
            'targetSchema' => array(
                'type' => 'object',
                'properties' => array(
                    'content' => array(
                        'raw' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        );

        if ('post' === $this->post_type) {
            $links[] = array(
                'rel' => 'https://api.w.org/action-sticky',
                'title' => __('The current user can sticky this post.', 'notificationx'),
                'href' => $href,
                'targetSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'sticky' => array(
                            'type' => 'boolean',
                        ),
                    ),
                ),
            );
        }

        if (post_type_supports($this->post_type, 'author')) {
            $links[] = array(
                'rel' => 'https://api.w.org/action-assign-author',
                'title' => __('The current user can change the author on this post.', 'notificationx'),
                'href' => $href,
                'targetSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'author' => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
            );
        }

        $taxonomies = wp_list_filter(get_object_taxonomies($this->post_type, 'objects'), array('show_in_rest' => true));

        foreach ($taxonomies as $tax) {
            $tax_base = !empty($tax->rest_base) ? $tax->rest_base : $tax->name;

            /* translators: %s: Taxonomy name. */
            $assign_title = sprintf(__('The current user can assign terms in the %s taxonomy.', 'notificationx'), $tax->name);
            /* translators: %s: Taxonomy name. */
            $create_title = sprintf(__('The current user can create terms in the %s taxonomy.', 'notificationx'), $tax->name);

            $links[] = array(
                'rel' => 'https://api.w.org/action-assign-' . $tax_base,
                'title' => $assign_title,
                'href' => $href,
                'targetSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        $tax_base => array(
                            'type' => 'array',
                            'items' => array(
                                'type' => 'integer',
                            ),
                        ),
                    ),
                ),
            );

            $links[] = array(
                'rel' => 'https://api.w.org/action-create-' . $tax_base,
                'title' => $create_title,
                'href' => $href,
                'targetSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        $tax_base => array(
                            'type' => 'array',
                            'items' => array(
                                'type' => 'integer',
                            ),
                        ),
                    ),
                ),
            );
        }

        return $links;
    }

    /**
     * Retrieves the query params for the posts collection.
     *
     * @since 4.7.0
     * @since 5.4.0 The `tax_relation` query parameter was added.
     * @since 5.7.0 The `modified_after` and `modified_before` query parameters were added.
     *
     * @return array Collection parameters.
     */
    public function get_collection_params() {
        $query_params = parent::get_collection_params();

        // $query_params['context']['default'] = 'view';

        // $query_params['after'] = array(
        //     'description' => __('Limit response to posts published after a given ISO8601 compliant date.'),
        //     'type' => 'string',
        //     'format' => 'date-time',
        // );

        // $query_params['modified_after'] = array(
        //     'description' => __('Limit response to posts modified after a given ISO8601 compliant date.'),
        //     'type' => 'string',
        //     'format' => 'date-time',
        // );

        // $query_params['before'] = array(
        //     'description' => __('Limit response to posts published before a given ISO8601 compliant date.'),
        //     'type' => 'string',
        //     'format' => 'date-time',
        // );

        // $query_params['modified_before'] = array(
        //     'description' => __('Limit response to posts modified before a given ISO8601 compliant date.'),
        //     'type' => 'string',
        //     'format' => 'date-time',
        // );

        // $query_params['exclude'] = array(
        //     'description' => __('Ensure result set excludes specific IDs.'),
        //     'type' => 'array',
        //     'items' => array(
        //         'type' => 'integer',
        //     ),
        //     'default' => array(),
        // );

        // $query_params['include'] = array(
        //     'description' => __('Limit result set to specific IDs.'),
        //     'type' => 'array',
        //     'items' => array(
        //         'type' => 'integer',
        //     ),
        //     'default' => array(),
        // );

        // $query_params['offset'] = array(
        //     'description' => __('Offset the result set by a specific number of items.'),
        //     'type' => 'integer',
        // );

        // $query_params['order'] = array(
        //     'description' => __('Order sort attribute ascending or descending.'),
        //     'type' => 'string',
        //     'default' => 'desc',
        //     'enum' => array('asc', 'desc'),
        // );

        // $query_params['orderby'] = array(
        //     'description' => __('Sort collection by object attribute.'),
        //     'type' => 'string',
        //     'default' => 'date',
        //     'enum' => array(
        //         'author',
        //         'date',
        //         'id',
        //         'include',
        //         'modified',
        //         'parent',
        //         'relevance',
        //         'slug',
        //         'include_slugs',
        //         'title',
        //     ),
        // );

        // $query_params['slug'] = array(
        //     'description' => __('Limit result set to posts with one or more specific slugs.'),
        //     'type' => 'array',
        //     'items' => array(
        //         'type' => 'string',
        //     ),
        //     'sanitize_callback' => 'wp_parse_slug_list',
        // );

        // $query_params['status'] = array(
        //     'default' => 'publish',
        //     'description' => __('Limit result set to posts assigned one or more statuses.'),
        //     'type' => 'array',
        //     'items' => array(
        //         'enum' => array_merge(array_keys(get_post_stati()), array('any')),
        //         'type' => 'string',
        //     ),
        //     'sanitize_callback' => array($this, 'sanitize_post_statuses'),
        // );

        return $query_params;
    }

    /**
     * Sanitizes and validates the list of post statuses, including whether the
     * user can query private statuses.
     *
     * @since 4.7.0
     *
     * @param string|array $statuses One or more post statuses.
     * @param WP_REST_Request $request Full details about the request.
     * @param string $parameter Additional parameter to pass to validation.
     * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
     */
    public function sanitize_post_statuses($statuses, $request, $parameter) {
        $statuses = wp_parse_slug_list($statuses);

        // The default status is different in WP_REST_Attachments_Controller.
        $attributes = $request->get_attributes();
        $default_status = $attributes['args']['status']['default'];

        foreach ($statuses as $status) {
            if ($status === $default_status) {
                continue;
            }

            $post_type_obj = get_post_type_object($this->post_type);

            if (current_user_can($post_type_obj->cap->edit_posts) || 'private' === $status && current_user_can($post_type_obj->cap->read_private_posts)) {
                $result = rest_validate_request_arg($status, $request, $parameter);
                if (is_wp_error($result)) {
                    return $result;
                }
            } else {
                return new WP_Error(
                    'rest_forbidden_status',
                    __('Status is forbidden.', 'notificationx'),
                    array('status' => rest_authorization_required_code())
                );
            }
        }

        return $statuses;
    }

}

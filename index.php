<?php
/*
Plugin Name: The Great Search
Plugin URI:  
Description: The best and simplest way for search an especific post in your blog
Author: Flávio Sena
Version: 0.1
Author URI: http://www.naiche.net
*/
$version = '0.1';


register_activation_hook(__FILE__, 'installgreatSearch');
register_deactivation_hook( __FILE__, 'uninstallgreatSearch');
add_filter('pre_get_posts', 'greatSearchFilters');
add_shortcode('getGreatSearchForm', 'getGreatSearchForm');

function installgreatSearch() {
    global $version;
	add_option('greatSearchVersion', $version);
}
function uninstallgreatSearch() {
	delete_option('greatSearchVersion');
}
function greatSearchFilters() {
    global $wp_query;
    if (isset($_REQUEST['s']) === TRUE) {
        $wp_query->query_vars['sentence'] = isset($_REQUEST['sentence']) && (int)$_REQUEST['sentence'] == 1 ? 1 : 0;
        
        
        if(isset($_REQUEST['cat']) === TRUE
                && $_REQUEST['cat'] != 0){
            $cat  = get_categories("hide_empty=false&exclude=" . $_REQUEST['cat']);
            $wp_query->query_vars['cat'] = "-" . $cat;
        }

        if(isset($_REQUEST['orderByDate']) === TRUE
            && strlen($_REQUEST['orderByDate']) > 0) {
            $wp_query->set('orderby', 'date');
            $wp_query->set('order', $_REQUEST['orderByDate'] == 'ASC' ? 'ASC' : 'DESC');
        }

        if(isset($_REQUEST['column']) === TRUE
            && strlen($_REQUEST['column']) > 0) {
            add_filter( 'posts_search', 'greatSearchByColumn', 500, 2 );
        }
    }
}
function getGreatSearchForm(){ ?>
    <form method="get" action="<?php echo get_bloginfo("url"); ?>">
                    <p>Critério de busca:
                        <input type="text" value="<?php echo esc_attr(apply_filters('the_search_query', get_search_query())) ?>" name="s" id="s" /></p>
                    <p>Em
                        <input type="radio" name="sentence" id="sentence0" checked value="0">
                        <label for="sentence0">qualquer palavra</label>

                        <input type="radio" name="sentence" id="sentence1" value="1">
                        <label for="sentence1">frase exata</label></p>
                    <p>Por
                        <input type="radio" name="column" id="columnall" checked value="">
                        <label for="columnall">Título e Conteudo</label>
                        <input type="radio" name="column" id="post_title" value="post_title">
                        <label for="post_title">Título</label>
                        <input type="radio" name="column" id="post_content" value="post_content">
                        <label for="post_content">Conteudo</label></p>
                    <p>
                        Order por: 
                        <select name="orderByDate">
                            <option value="DESC">Mais recentes</option>
                            <option value="ASC">Mais antigos</option>
                        </select>
                    </p>
                    <p>Categoria:
                    <?php echo wp_dropdown_categories(array(
                        'show_option_all' => 'Todas as categorias',
                        'show_option_none' => '',
                        'orderby' => 'name',
                        'order' => 'ASC',
                        'show_last_update' => 0,
                        'show_count' => 0,
                        'hide_empty' => 1,
                        'child_of' => 0,
                        'echo' => 0,
                        'selected' => (int)$_GET['cat'],
                        'hierarchical' => 1, 
                        'name' => 'cat',
                        'class' => 'cat-list')) ?>
                    </p>
            <input type="submit" value="Buscar" />
    </form>
<?php
}
function greatSearchByColumn($search, &$wp_query) {
    if ( empty( $search ) ) return $search;

    $column = $_REQUEST['column'];
    if(strlen($column) > 0) {
        global $wpdb;
        $n = ! empty( $wp_query->query_vars['exact'] ) ? '' : '%';
        $search =
        $and = NULL;
        foreach ( (array) $wp_query->query_vars['search_terms'] as $term ) {
            $term = esc_sql( like_escape( $term ) );
            $search .= $and . "(" . $wpdb->posts . "." . $column . " LIKE '" . $n . $term . $n . "')";
            $and = ' AND ';
        }
        if ( ! empty( $search ) ) {
            $search = " AND ({$search}) ";
            if ( ! is_user_logged_in() )
                $search .= " AND ($wpdb->posts.post_password = '') ";
        }
    }
    return $search;
}
<?php
/**
 * Plugin Name: Polvor Post Grids
 * Description: Exibe posts de um blog externo em um grid com atualização automática.
 * Version: 1.3
 * Author: Polvor
 */

// Cria a página de configurações do plugin no menu lateral
function custom_post_grid_menu() {
    add_menu_page('Polvor Post Grids', 'Polvor Post Grids', 'manage_options', 'custom-post-grid', 'custom_post_grid_options_page', 'dashicons-layout', 6);
}
add_action('admin_menu', 'custom_post_grid_menu');

// Registra as configurações do plugin
function custom_post_grid_settings() {
    register_setting('custom_post_grid_options_group', 'custom_post_grid_url');
    register_setting('custom_post_grid_options_group', 'custom_post_grid_num_posts');
    register_setting('custom_post_grid_options_group', 'custom_post_grid_columns');
    register_setting('custom_post_grid_options_group', 'custom_post_grid_excerpt_length');
    register_setting('custom_post_grid_options_group', 'custom_post_grid_show_read_more');
}
add_action('admin_init', 'custom_post_grid_settings');

// Enqueue CSS para a interface do plugin
function custom_post_grid_admin_styles() {
    echo '<style>
        .custom-post-grid-settings {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .custom-post-grid-settings h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
        }
        .custom-post-grid-settings .form-table {
            margin-bottom: 20px;
        }
        .custom-post-grid-settings th {
            text-align: left;
            font-weight: bold;
            color: #555;
        }
        .custom-post-grid-settings td {
            padding: 10px 0;
        }
        .custom-post-grid-settings input[type="text"],
        .custom-post-grid-settings input[type="number"],
        .custom-post-grid-settings input[type="checkbox"] {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .custom-post-grid-settings input[type="checkbox"] {
            width: auto;
            display: inline-block;
        }
        .custom-post-grid-settings .button-primary {
            background-color: #5C2BC0;
            border: none;
            color: white;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin-top: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .custom-post-grid-settings .button-primary:hover {
            background-color: #5C2BC0;
        }
        .custom-post-grid-settings code {
            background-color: #f1f1f1;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .custom-post-grid-settings .shortcode-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>';
}
add_action('admin_head', 'custom_post_grid_admin_styles');

// Cria a interface de configurações
function custom_post_grid_options_page() {
    ?>
    <div class="wrap custom-post-grid-settings">
        <h1>Configure seu shortcode de posts externos - Polvor Tecnologia e Software</h1>
        <form method="post" action="options.php">
            <?php settings_fields('custom_post_grid_options_group'); ?>
            <?php do_settings_sections('custom_post_grid_options_group'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">URL do Blog</th>
                    <td><input type="text" name="custom_post_grid_url" value="<?php echo esc_attr(get_option('custom_post_grid_url')); ?>" /></td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Número de Posts</th>
                    <td><input type="number" name="custom_post_grid_num_posts" value="<?php echo esc_attr(get_option('custom_post_grid_num_posts', 5)); ?>" /></td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Número de Colunas</th>
                    <td><input type="number" name="custom_post_grid_columns" value="<?php echo esc_attr(get_option('custom_post_grid_columns', 3)); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Número de Palavras no Resumo</th>
                    <td><input type="number" name="custom_post_grid_excerpt_length" value="<?php echo esc_attr(get_option('custom_post_grid_excerpt_length', 20)); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Mostrar "Leia mais"</th>
                    <td>
                        <label><input type="checkbox" name="custom_post_grid_show_read_more" value="1" <?php checked(1, get_option('custom_post_grid_show_read_more', 0)); ?> /> Ativar</label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <div class="shortcode-section">
            <h2>Use o Shortcode</h2>
            <p>Copie e cole o shortcode abaixo em uma página ou post:</p>
            <code>[custom_post_grid]</code>
        </div>
    </div>
    <?php
}

// Adiciona o shortcode [custom_post_grid]
function custom_post_grid_shortcode($atts) {
    // Pega as opções do banco de dados
    $url = get_option('custom_post_grid_url');
    $num_posts = get_option('custom_post_grid_num_posts', 5);
    $columns = get_option('custom_post_grid_columns', 3);
    $excerpt_length = get_option('custom_post_grid_excerpt_length', 20);
    $show_read_more = get_option('custom_post_grid_show_read_more', 0);

    // Verifica se a URL foi fornecida
    if (empty($url)) {
        return 'Por favor, forneça uma URL válida.';
    }

    // Pega os posts do blog externo
    $response = wp_remote_get($url . '/wp-json/wp/v2/posts?per_page=' . $num_posts);
    
    if (is_wp_error($response)) {
        return 'Erro ao buscar os posts.';
    }

    $posts = json_decode(wp_remote_retrieve_body($response));

    // Inicia a saída HTML
    $output = '<div class="custom-post-grid" style="display: grid; grid-template-columns: repeat(' . esc_attr($columns) . ', 1fr); gap: 20px;">';

    foreach ($posts as $post) {
        // Obtém a imagem destacada, se houver
        $image_url = isset($post->featured_media) ? wp_remote_get($url . '/wp-json/wp/v2/media/' . $post->featured_media) : null;
        $image_data = json_decode(wp_remote_retrieve_body($image_url));

        $output .= '<div class="post-item">';
        
        // Adiciona a imagem se existir
        if (isset($image_data->source_url)) {
            $output .= '<a href="' . esc_url($post->link) . '" target="_blank"><img src="' . esc_url($image_data->source_url) . '" alt="' . esc_attr($post->title->rendered) . '" /></a>';
        }

        // Adiciona o título do post
        $output .= '<a href="' . esc_url($post->link) . '" target="_blank"><h2>' . esc_html($post->title->rendered) . '</h2></a>';

        // Adiciona o resumo do post
        $excerpt = wp_trim_words(strip_tags($post->excerpt->rendered), $excerpt_length);
        $output .= '<p>' . esc_html($excerpt) . '</p>';

        // Adiciona "Leia mais" se ativado
        if ($show_read_more) {
            $output .= '<a href="' . esc_url($post->link) . '" target="_blank">Leia mais</a>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('custom_post_grid', 'custom_post_grid_shortcode');

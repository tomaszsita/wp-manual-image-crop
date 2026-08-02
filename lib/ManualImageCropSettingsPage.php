<?php
class MicSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }
    
    /**
     * Fix of settings serialized twice or more
     * @return mixed
     */
    static function getSettings() {
    	$micOptions = get_option( 'mic_options' );
    	if ( ! isset( $micOptions['sizes_settings'] ) ) {
    		return array();
    	}
    	$settings = unserialize( $micOptions['sizes_settings'] );
    	$i = 0;
    	while ( ! empty($settings) && ! is_array($settings) ) {
    		if ($i++ == 10) {
    			break;
    		}
    		$settings = unserialize($settings);
    	}
    	// Ensure we always return an array, not false
    	return is_array($settings) ? $settings : array();
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Manual Image Crop Settings', 'microp'), 
            __('Manual Image Crop', 'microp'), 
            'manage_options', 
            'Mic-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('Manual Image Crop Settings', 'microp'); ?></h2>
            
            <!-- System Information Section -->
            <div class="card" style="max-width: 100%; margin-bottom: 20px;">
                <h3><?php _e('System Information', 'microp'); ?></h3>
                <table class="widefat" style="margin-top: 10px;">
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Supported Image Formats:', 'microp'); ?></strong></td>
                            <td><?php echo $this->get_supported_formats(); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('PHP Version:', 'microp'); ?></strong></td>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('GD Library:', 'microp'); ?></strong></td>
                            <td><?php echo $this->get_gd_info(); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('ImageMagick:', 'microp'); ?></strong></td>
                            <td><?php echo $this->get_imagick_info(); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('WordPress Version:', 'microp'); ?></strong></td>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Plugin Version:', 'microp'); ?></strong></td>
                            <td><?php echo mic_VERSION; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <form method="post" action="options.php" class="mic-settings-page">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'mic_options_group' );   
                do_settings_sections( 'Mic-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'mic_options_group', // Option group
            'mic_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            __('Mic Custom Settings', 'microp'), // Title
            array( $this, 'print_section_info' ), // Callback
            'Mic-setting-admin' // Page
        );  

        add_settings_field(
            'sizes_settings', // ID
            __('Crop sizes settings', 'microp'), // Title 
            array( $this, 'sizes_settings_callback' ), // Callback
            'Mic-setting-admin', // Page
            'setting_section_id' // Section           
        );          
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['sizes_settings'] ) ) {
            $new_input['sizes_settings'] = serialize( $input['sizes_settings'] );
        }
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print __('Enter your settings below:', 'microp');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function sizes_settings_callback()
    {
		global $_wp_additional_image_sizes;
		
    	$imageSizes = get_intermediate_image_sizes();
    	
        $sizeLabels = apply_filters( 'image_size_names_choose', array(
            'thumbnail' => __('Thumbnail'),
            'medium'    => __('Medium'),
            'large'     => __('Large'),
            'full'      => __('Full Size'),
        ) );
        $sizeLabels = apply_filters( 'image_size_names_choose', array() );
		
		echo '<div class="mic-settings-table-wrapper">';
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead>
			  <tr>
			     <th scope="col" class="manage-column column-size">' . __('Size', 'microp') . '</th>
			     <th scope="col" class="manage-column column-visible">' . __('Visible', 'microp') . '</th>
			     <th scope="col" class="manage-column column-quality">' . __('Default JPEG Quality', 'microp') . '</th>
			     <th scope="col" class="manage-column column-label">' . __('Custom Label', 'microp') . '</th>
			  </tr>
			 </thead>
             <tbody>';
		
		$sizesSettings = self::getSettings();
		if (!is_array($sizesSettings)) {
			$sizesSettings = array();
		}
		
		foreach ($imageSizes as $s) {
			$label = isset($sizeLabels[$s]) ? $sizeLabels[$s] : ucfirst( str_replace( '-', ' ', $s ) );
			if (isset($_wp_additional_image_sizes[$s])) {
				$cropMethod = $_wp_additional_image_sizes[$s]['crop'];
			} else {
				$cropMethod = get_option($s.'_crop');
			}
			
			if ($cropMethod == 0) {
				continue;
			}
			
			echo '<tr>
			     <td>' . $label. '</td>
			     <td><select name="mic_options[sizes_settings][' . $s . '][visibility]">
     					<option value="visible">' . __('Yes', 'microp') . '</option>
     					<option value="hidden" ' . ( $sizesSettings[$s]['visibility'] == 'hidden' ? 'selected' : '' ) . '>' . __('No', 'microp') . '</option>
    				</select></td>
			     <td><select name="mic_options[sizes_settings][' . $s . '][quality]">
     					<option value="100">' . __('100 (best quality, biggest file)', 'microp') . '</option>
     					<option value="80" ' . ( !isset ($sizesSettings[$s]['quality']) || $sizesSettings[$s]['quality'] == '80' ? 'selected' : '' ) . '>' . __('80 (very high quality)', 'microp') . '</option>
     					<option value="70" ' . ( $sizesSettings[$s]['quality'] == '70' ? 'selected' : '' ) . '>' . __('70 (high quality)', 'microp') . '</option>
     					<option value="60" ' . ( $sizesSettings[$s]['quality'] == '60' ? 'selected' : '' ) . '>' . __('60 (good)', 'microp') . '</option>
     					<option value="50" ' . ( $sizesSettings[$s]['quality'] == '50' ? 'selected' : '' ) . '>' . __('50 (average)', 'microp') . '</option>
     					<option value="30" ' . ( $sizesSettings[$s]['quality'] == '30' ? 'selected' : '' ) . '>' . __('30 (low)', 'microp') . '</option>
     					<option value="10" ' . ( $sizesSettings[$s]['quality'] == '10' ? 'selected' : '' ) . '>' . __('10 (very low, smallest file)', 'microp') . '</option>
    				</select></td>
			     <td><input name="mic_options[sizes_settings][' . $s . '][label]" type="text" placeholder="' . $label . '" value="' . str_replace('"', '&quot;', isset($sizesSettings[$s]['label']) ? $sizesSettings[$s]['label'] : '') .  '"/></td>
			</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
		
    }
    
    /**
     * Get supported image formats information
     */
    private function get_supported_formats() {
        $formats = array();
        
        // Check GD formats
        if (function_exists('gd_info')) {
            $gd_info = gd_info();
            if (isset($gd_info['JPEG Support']) && $gd_info['JPEG Support']) {
                $formats[] = 'JPEG';
            }
            if (isset($gd_info['PNG Support']) && $gd_info['PNG Support']) {
                $formats[] = 'PNG';
            }
            if (isset($gd_info['GIF Read Support']) && $gd_info['GIF Read Support']) {
                $formats[] = 'GIF';
            }
            if (function_exists('imagewebp')) {
                $formats[] = 'WebP';
            }
        }
        
        // Check Imagick formats
        if (class_exists('Imagick')) {
            try {
                $imagick = new Imagick();
                $formats_imagick = $imagick->queryFormats();
                
                if (in_array('WEBP', $formats_imagick) && !in_array('WebP', $formats)) {
                    $formats[] = 'WebP (Imagick)';
                }
                if (in_array('TIFF', $formats_imagick)) {
                    $formats[] = 'TIFF';
                }
                if (in_array('BMP', $formats_imagick)) {
                    $formats[] = 'BMP';
                }
            } catch (Exception $e) {
                // Imagick error, skip
            }
        }
        
        return implode(', ', $formats);
    }
    
    /**
     * Get GD Library information
     */
    private function get_gd_info() {
        if (!function_exists('gd_info')) {
            return __('Not available', 'microp');
        }
        
        $gd_info = gd_info();
        $version = isset($gd_info['GD Version']) ? $gd_info['GD Version'] : __('Unknown', 'microp');
        
        $features = array();
        if (isset($gd_info['JPEG Support']) && $gd_info['JPEG Support']) {
            $features[] = 'JPEG';
        }
        if (isset($gd_info['PNG Support']) && $gd_info['PNG Support']) {
            $features[] = 'PNG';
        }
        if (isset($gd_info['GIF Read Support']) && $gd_info['GIF Read Support']) {
            $features[] = 'GIF';
        }
        if (function_exists('imagewebp')) {
            $features[] = 'WebP';
        }
        
        return sprintf('%s (%s)', $version, implode(', ', $features));
    }
    
    /**
     * Get ImageMagick information
     */
    private function get_imagick_info() {
        if (!class_exists('Imagick')) {
            return __('Not available', 'microp');
        }
        
        try {
            $imagick = new Imagick();
            $version = $imagick->getVersion();
            $version_info = $version['versionString'];
            
            $formats = $imagick->queryFormats();
            $supported_formats = array();
            
            $check_formats = array('JPEG', 'PNG', 'GIF', 'WEBP', 'TIFF', 'BMP');
            foreach ($check_formats as $format) {
                if (in_array($format, $formats)) {
                    $supported_formats[] = $format;
                }
            }
            
            return sprintf('%s (%s)', $version_info, implode(', ', $supported_formats));
        } catch (Exception $e) {
            return __('Available but error occurred', 'microp');
        }
    }
}

if( is_admin() ) {
    $mic_settings_page = new MicSettingsPage();
}
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

        add_action( 'wp_print_scripts', array( $this, 'print_autocrop_js_var' ) );
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
    	return $settings;
    }

    /**
     * Return whether or not to automatically open crop page for uploaded images
     * @return int
     */
    static function getAutocropUploadSetting() {
        $micOptions = get_option( 'mic_options' );
    	return empty($micOptions['autocrop_uploads']) ? 0 : 1;
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
            null, // Title
            null, // Callback
            'Mic-setting-admin' // Page
        );

        add_settings_field(
            'sizes_settings', // ID
            __('Crop sizes settings', 'microp'), // Title
            array( $this, 'sizes_settings_callback' ), // Callback
            'Mic-setting-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            'autocrop_uploads', // ID
            __('Autocrop uploaded images setting', 'microp'), // Title
            array( $this, 'autocrop_uploads_callback' ), // Callback
            'Mic-setting-admin', // Page
            'setting_section_id' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     *
     * @return array
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['sizes_settings'] ) ) {
            $new_input['sizes_settings'] = serialize( $input['sizes_settings'] );
        }
        if( isset( $input['autocrop_uploads'] ) ) {
            $new_input['autocrop_uploads'] = 1;
        }else{
            $new_input['autocrop_uploads'] = 0;
        }
        return $new_input;
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
		
		echo '<table class="widefat fixed mic-table striped" cellspacing="0">';
		echo '<thead>
			  <tr>
			     <th class="mic-size">' . __('Size', 'microp') . '</th>
			     <th class="mic-visible">' . __('Visible', 'microp') . '</th>
			     <th class="mic-quality">' . __('Default JPEG Quality', 'microp') . '</th>
			     <th class="mic-label">' . __('Custom Label', 'microp') . '</th>
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
			     <td class="mic-size">' . $label. '</td>
			     <td class="mic-visible"><select name="mic_options[sizes_settings][' . $s . '][visibility]">
     					<option value="visible">' . __('Yes', 'microp') . '</option>
     					<option value="hidden" ' . ( $sizesSettings[$s]['visibility'] == 'hidden' ? 'selected' : '' ) . '>' . __('No', 'microp') . '</option>
    				</select></td>
			     <td class="mic-quality"><select name="mic_options[sizes_settings][' . $s . '][quality]">
     					<option value="100">' . __('100 (best quality, biggest file)', 'microp') . '</option>
     					<option value="80" ' . ( !isset ($sizesSettings[$s]['quality']) || $sizesSettings[$s]['quality'] == '80' ? 'selected' : '' ) . '>' . __('80 (very high quality)', 'microp') . '</option>
     					<option value="70" ' . ( $sizesSettings[$s]['quality'] == '70' ? 'selected' : '' ) . '>' . __('70 (high quality)', 'microp') . '</option>
     					<option value="60" ' . ( $sizesSettings[$s]['quality'] == '60' ? 'selected' : '' ) . '>' . __('60 (good)', 'microp') . '</option>
     					<option value="50" ' . ( $sizesSettings[$s]['quality'] == '50' ? 'selected' : '' ) . '>' . __('50 (average)', 'microp') . '</option>
     					<option value="30" ' . ( $sizesSettings[$s]['quality'] == '30' ? 'selected' : '' ) . '>' . __('30 (low)', 'microp') . '</option>
     					<option value="10" ' . ( $sizesSettings[$s]['quality'] == '10' ? 'selected' : '' ) . '>' . __('10 (very low, smallest file)', 'microp') . '</option>
    				</select></td>
			     <td class="mic-label"><input name="mic_options[sizes_settings][' . $s . '][label]" type="text" placeholder="' . $label . '" value="' . str_replace('"', '&quot;', $sizesSettings[$s]['label']) .  '"/></td>
			</tr>';
		}
		echo '</tbody></table>';
		
    }

    /** 
     * Display settings for the autocrop option
     */
    public function autocrop_uploads_callback()
    {
        $autocrop = self::getAutocropUploadSetting();

        ?>
        <p>
            <label>
                <input type="checkbox" name="mic_options[autocrop_uploads]" <?php checked($autocrop); ?>>
                <?php _e('Automatically ask to crop images on upload', 'microp'); ?>
            </label>
        </p>
        <?php
		
    }

    /**
     * Sets a js variable is autocrop is enabled. Variable contains the title of the popup, which can be translated
     */
    public function print_autocrop_js_var()
    {
        if ( !is_admin() ) return;

        $autocrop = self::getAutocropUploadSetting();
        if ( !$autocrop ) return;

        ?>
        <script type="text/javascript">window.mic_autocrop_uploads = <?php echo json_encode( __("Manual Image Crop","microp") ); ?>;</script>
        <?php

    }
}

if( is_admin() ) {
    $mic_settings_page = new MicSettingsPage();
}
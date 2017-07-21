<?php
/*
Plugin Name: Validar DNI NIF NIE y CIF
Plugin URI: http://www.hodeidesign.com
Description: Valida campos de DNI, NIF, NIE y CIF utilizando el plugin Contact Form 7
Version: 1.1
Author: Hodei Design
Author URI: http://www.hodeidesign.com
License: GPL2

#_________________________________________________ LICENSE

Copyright 2014 Hodei Design (email : hola@hodeidesign.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


function cf7_requerido()
{
	$plugin_messages = '';
	
	 if ( file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ){
		if(!is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ))
		{
			$plugin_messages = 'El plugin validación DNI-NIF-NIE-CIF requiere que el plugin Contact Form 7 esté activado';
		}
	 }else{
		// Download contact form 7
		$plugin_messages = 'El plugin validación DNI-NIF-NIE-CIF requiere el plugin Contact Form 7, <a href="https://wordpress.org/plugins/contact-form-7/">descargalo aquí</a>.';
	 }
	
	if(!empty($plugin_messages))
	{
		echo '<div id="message" class="error">';

			
			echo '<p><strong>'.$plugin_messages.'</strong></p>';


		echo '</div>';
	}
}

add_action('admin_notices', 'cf7_requerido');

if ( file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ){
	if(is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )){
		// Lógica validación
		function valida_nif_cif_nie($identidad) {
		//Copyright ©2005-2011 David Vidal Serra. Bajo licencia GNU GPL.
		//Este software viene SIN NINGUN TIPO DE GARANTIA; para saber mas detalles
		//puede consultar la licencia en http://www.gnu.org/licenses/gpl.txt(1)
		//Esto es software libre, y puede ser usado y redistribuirdo de acuerdo
		//con la condicion de que el autor jamas sera responsable de su uso.
		//Returns: 1 = NIF ok, 2 = CIF ok, 3 = NIE ok, -1 = NIF bad, -2 = CIF bad, -3 = NIE bad, 0 = ??? bad
			$identidad = strtoupper($identidad);
			for ($i = 0; $i < 9; $i ++)
			{
				$num[$i] = substr($identidad, $i, 1);
			}
			//si no tiene un formato valido devuelve error
			if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $identidad))
			{
				return 0;
			}
			//comprobacion de NIFs estandar
			if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $identidad))
			{
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($identidad, 0, 8) % 23, 1))
				{
					return 1;
				}
				else
				{
					return -1;
				}
			}
			//algoritmo para comprobacion de codigos tipo CIF
			$suma = $num[2] + $num[4] + $num[6];
			for ($i = 1; $i < 8; $i += 2)
			{
				$suma += substr((2 * $num[$i]),0,1) + substr((2 * $num[$i]), 1, 1);
			}
			$n = 10 - substr($suma, strlen($suma) - 1, 1);
			//comprobacion de NIFs especiales (se calculan como CIFs o como NIFs)
			if (preg_match('/^[KLM]{1}/', $identidad))
			{
				if ($num[8] == chr(64 + $n) || $num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($identidad, 1, 8)
				% 23, 1))
				{
					return 1;
				}
				else
				{
					return -1;
				}
			}
			//comprobacion de CIFs
			if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $identidad))
			{
				if ($num[8] == chr(64 + $n) || $num[8] == substr($n, strlen($n) - 1, 1))
				{
					return 2;
				}
				else
				{
					return -2;
				}
			}
			//comprobacion de NIEs
			if (preg_match('/^[XYZ]{1}/', $identidad))
			{
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr(str_replace(array('X','Y','Z'),
				array('0','1','2'), $identidad), 0, 8) % 23, 1))
				{
					return 3;
				}
				else{
					return -3;
				}
			}
		//si todavia no se ha verificado devuelve error
		return 0;
		}

		function cf7_nif_cif_nie_validacion($result, $tag) {
			$result_actual = $result['valid'];

			//$type = $tag['type'];
			$type = $tag['basetype'];
			$name = $tag['name'];
			//Si esta vacio y es obligatorio lanzar error invalid_required
			if($type == 'text*' && $_POST[$name] == ''){
					//$result['valid'] = false;
					//$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );	
			}
			
			//validaciones

			//dni,cif,nie o nif 
			if($name == 'identidad') {
				$identidad = $_POST['identidad'];
				
				if($identidad != '') {
					if(valida_nif_cif_nie($identidad) == 0 || valida_nif_cif_nie($identidad) == -1 || valida_nif_cif_nie($identidad) == -2 
					|| valida_nif_cif_nie($identidad) == -3){
						//$result['valid'] = false;
						//$result['reason'][$name] = 'Escribe un DNI, NIF, NIE o CIF válido';
						$result->invalidate( $tag, wpcf7_get_message( 'validation_error' ) );						

					}else{
						if($result_actual == false){
							$result['valid'] = false;
						}else{
							$result['valid'] = true;
						}
					}			
				}
			}

			
			return $result;
			
		}

		//add filter para validación text
		add_filter( 'wpcf7_validate_text', 'cf7_nif_cif_nie_validacion', 10, 2 );
		add_filter( 'wpcf7_validate_text*', 'cf7_nif_cif_nie_validacion', 10, 2 );
	}
}

?>

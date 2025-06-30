<?php
/**
 * Handles WebAuthn authentication logic for webauthn-lib v4.x.
 *
 * @package Sms_Login_Register/Includes/Core
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Import the necessary classes and interfaces from the library using the 'use' keyword.
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Base64Url;

/**
 * Class SLR_WebAuthn_Handler.
 *
 * This class now correctly implements the namespaced interface.
 */
class SLR_WebAuthn_Handler implements PublicKeyCredentialSourceRepository {

	/**
	 * Finds a credential source by its ID.
	 *
	 * @param string $publicKeyCredentialId The credential ID (as binary).
	 *
	 * @return PublicKeyCredentialSource|null
	 */
	public function findOneByCredentialId( string $publicKeyCredentialId ): ?PublicKeyCredentialSource {
		// The library provides the ID as a binary string.
		// We need to base64url_encode it to match how we might have stored it.
		$credential_id_base64 = Base64Url::encode( $publicKeyCredentialId );
		$all_users_credentials = get_users( [
			'meta_key'   => '_slr_webauthn_credentials_id',
			'meta_value' => $credential_id_base64,
			'number'     => 1,
			'fields'     => 'ID',
		] );

		if ( empty( $all_users_credentials ) ) {
			return null;
		}

		$user_id = $all_users_credentials[0];
		// In this version, the repository method is `findAllForUserEntity`, not `findAllForUserHandle`.
		$user_entity = $this->findUserEntityById( $user_id );
		if ( $user_entity === null ) {
			return null;
		}

		$all_sources = $this->findAllForUserEntity( $user_entity );

		foreach ( $all_sources as $source ) {
			if ( $source->getPublicKeyCredentialId() === $credential_id_base64 ) {
				return $source;
			}
		}

		return null;
	}


	/**
	 * Finds all credential sources for a given user entity.
	 *
	 * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity The user entity.
	 *
	 * @return PublicKeyCredentialSource[]
	 */
	public function findAllForUserEntity( PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity ): array {
		$user_id      = $publicKeyCredentialUserEntity->getId();
		$sources_data = get_user_meta( $user_id, '_slr_webauthn_credentials', true );

		if ( empty( $sources_data ) || ! is_array( $sources_data ) ) {
			return [];
		}

		$sources = [];
		foreach ( $sources_data as $source_json ) {
			try {
				$sources[] = PublicKeyCredentialSource::createFromArray( json_decode( $source_json, true ) );
			} catch ( \Exception $e ) {
				// Log or handle error if deserialization fails.
				continue;
			}
		}
		return $sources;
	}


	/**
	 * Saves a credential source.
	 *
	 * @param PublicKeyCredentialSource $publicKeyCredentialSource The credential source to save.
	 */
	public function saveCredentialSource( PublicKeyCredentialSource $publicKeyCredentialSource ): void {
		$user_id      = $publicKeyCredentialSource->getUserHandle();
		$sources_data = get_user_meta( $user_id, '_slr_webauthn_credentials', true );
		if ( ! is_array( $sources_data ) ) {
			$sources_data = [];
		}

		// Remove old entry if it exists, and add the new one.
		$found = false;
		foreach ( $sources_data as $key => $source_json ) {
			$data = json_decode( $source_json, true );
			if ( $data['publicKeyCredentialId'] === $publicKeyCredentialSource->getPublicKeyCredentialId() ) {
				$sources_data[ $key ] = json_encode( $publicKeyCredentialSource );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$sources_data[] = json_encode( $publicKeyCredentialSource );
		}

		update_user_meta( $user_id, '_slr_webauthn_credentials', $sources_data );

		// Also save the credential ID separately for faster lookups.
		add_user_meta( $user_id, '_slr_webauthn_credentials_id', $publicKeyCredentialSource->getPublicKeyCredentialId(), false );
	}

	/**
	 * Helper function to get a user entity by ID.
	 *
	 * @param string $user_id The user ID.
	 * @return PublicKeyCredentialUserEntity|null
	 */
	private function findUserEntityById( string $user_id ): ?PublicKeyCredentialUserEntity {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return null;
		}
		return new PublicKeyCredentialUserEntity(
			$user->user_login,
			$user->ID,
			$user->display_name,
			null // icon property does not exist in v4
		);
	}
}
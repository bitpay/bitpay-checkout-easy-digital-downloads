<?php
/**
 * WordPress Coding Standard.
 *
 * @package WPCS\WordPressCodingStandards
 * @link    https://github.com/WordPress/WordPress-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace WordPressCS\WordPress\Sniffs\Security;

use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\TextStrings;
use WordPressCS\WordPress\Helpers\ContextHelper;
use WordPressCS\WordPress\Helpers\ValidationHelper;
use WordPressCS\WordPress\Helpers\VariableHelper;
use WordPressCS\WordPress\Sniff;

/**
 * Flag any non-validated/sanitized input ( _GET / _POST / etc. ).
 *
 * @link    https://github.com/WordPress/WordPress-Coding-Standards/issues/69
 *
 * @package WPCS\WordPressCodingStandards
 *
 * @since   0.3.0
 * @since   0.4.0  This class now extends the WordPressCS native `Sniff` class.
 * @since   0.5.0  Method getArrayIndexKey() has been moved to the WordPressCS native `Sniff` class.
 * @since   0.13.0 Class name changed: this class is now namespaced.
 * @since   1.0.0  This sniff has been moved from the `VIP` category to the `Security` category.
 *
 * @uses    \WordPressCS\WordPress\Helpers\SanitizingFunctionsTrait::$customSanitizingFunctions
 * @uses    \WordPressCS\WordPress\Helpers\SanitizingFunctionsTrait::$customUnslashingSanitizingFunctions
 */
class ValidatedSanitizedInputSniff extends Sniff {

	/**
	 * Check for validation functions for a variable within its own parenthesis only.
	 *
	 * @var boolean
	 */
	public $check_validation_in_scope_only = false;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			\T_VARIABLE,
			\T_DOUBLE_QUOTED_STRING,
			\T_HEREDOC,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param int $stackPtr The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process_token( $stackPtr ) {

		$superglobals = $this->input_superglobals;

		// Handling string interpolation.
		if ( \T_DOUBLE_QUOTED_STRING === $this->tokens[ $stackPtr ]['code']
			|| \T_HEREDOC === $this->tokens[ $stackPtr ]['code']
		) {
			// Retrieve all embeds, but use only the initial variable name part.
			$interpolated_variables = array_map(
				function( $embed ) {
					return '$' . preg_replace( '`^(\{?\$\{?\(?)([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)(.*)$`', '$2', $embed );
				},
				TextStrings::getEmbeds( $this->tokens[ $stackPtr ]['content'] )
			);

			foreach ( array_intersect( $interpolated_variables, $superglobals ) as $bad_variable ) {
				$this->phpcsFile->addError( 'Detected usage of a non-sanitized, non-validated input variable %s: %s', $stackPtr, 'InputNotValidatedNotSanitized', array( $bad_variable, $this->tokens[ $stackPtr ]['content'] ) );
			}

			return;
		}

		// Check if this is a superglobal.
		if ( ! \in_array( $this->tokens[ $stackPtr ]['content'], $superglobals, true ) ) {
			return;
		}

		// If we're overriding a superglobal with an assignment, no need to test.
		if ( VariableHelper::is_assignment( $this->phpcsFile, $stackPtr ) ) {
			return;
		}

		// This superglobal is being validated.
		if ( ContextHelper::is_in_isset_or_empty( $this->phpcsFile, $stackPtr ) ) {
			return;
		}

		$array_keys = VariableHelper::get_array_access_keys( $this->phpcsFile, $stackPtr );

		if ( empty( $array_keys ) ) {
			return;
		}

		$error_data = array( $this->tokens[ $stackPtr ]['content'] . '[' . implode( '][', $array_keys ) . ']' );

		/*
		 * Check for validation first.
		 */
		$validated = false;

		for ( $i = ( $stackPtr + 1 ); $i < $this->phpcsFile->numTokens; $i++ ) {
			if ( isset( Tokens::$emptyTokens[ $this->tokens[ $i ]['code'] ] ) ) {
				continue;
			}

			if ( \T_OPEN_SQUARE_BRACKET === $this->tokens[ $i ]['code']
				&& isset( $this->tokens[ $i ]['bracket_closer'] )
			) {
				// Skip over array keys.
				$i = $this->tokens[ $i ]['bracket_closer'];
				continue;
			}

			if ( \T_COALESCE === $this->tokens[ $i ]['code'] ) {
				$validated = true;
			}

			// Anything else means this is not a validation coalesce.
			break;
		}

		if ( false === $validated ) {
			$validated = ValidationHelper::is_validated( $this->phpcsFile, $stackPtr, $array_keys, $this->check_validation_in_scope_only );
		}

		if ( false === $validated ) {
			$this->phpcsFile->addError(
				'Detected usage of a possibly undefined superglobal array index: %s. Use isset() or empty() to check the index exists before using it',
				$stackPtr,
				'InputNotValidated',
				$error_data
			);
		}

		// If this variable is being tested with one of the `is_..()` functions, sanitization isn't needed.
		if ( ContextHelper::is_in_type_test( $this->phpcsFile, $stackPtr ) ) {
			return;
		}

		// If this is a comparison ('a' == $_POST['foo']), sanitization isn't needed.
		if ( VariableHelper::is_comparison( $this->phpcsFile, $stackPtr, false ) ) {
			return;
		}

		// If this is a comparison using the array comparison functions, sanitization isn't needed.
		if ( ContextHelper::is_in_array_comparison( $this->phpcsFile, $stackPtr ) ) {
			return;
		}

		// Now look for sanitizing functions.
		if ( ! $this->is_sanitized( $stackPtr, true ) ) {
			$this->phpcsFile->addError(
				'Detected usage of a non-sanitized input variable: %s',
				$stackPtr,
				'InputNotSanitized',
				$error_data
			);
		}
	}
}

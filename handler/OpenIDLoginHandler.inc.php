<?php

import('classes.handler.Handler');

class OpenIDLoginHandler extends Handler
{


	/**
	 * Disables the default login.
	 * This function redirects to the oauth login page.
	 *
	 * @param $args
	 * @param $request
	 */
	function index($args, $request)
	{
		if (!Validation::isLoggedIn()) {
			$plugin = PluginRegistry::getPlugin('generic', KEYCLOAK_PLUGIN_NAME);
			$router = $request->getRouter();
			$context = Application::get()->getRequest()->getContext();
			$contextId = ($context == null) ? 0 : $context->getId();
			$settingsJson = $plugin->getSetting($contextId, 'openIDSettings');
			if ($settingsJson != null) {
				$settings = json_decode($settingsJson, true);
				if (key_exists('authUrl', $settings) && key_exists('clientId', $settings)) {
					$request->redirectUrl(
						$settings['authUrl'].
						'?client_id='.$settings['clientId'].
						'&response_type=code&scope=openid&redirect_uri='.
						$router->url($request, null, "openid", "doAuthentication")
					);
				}
			}
		}
		$request->redirect(Application::get()->getRequest()->getContext(), 'index');
	}

	/**
	 * Disables the default registration, because it is not needed anymore.
	 * User registration is done via oauth.
	 *
	 * @param $args
	 * @param $request
	 */
	function register($args, $request)
	{
		$this->index($args, $request);
	}

	/**
	 * Disables default logout.
	 * This function redirects to the oauth logout to delete session and tokens.
	 * It uses a redirect_uri parameter (/login/signOutOjs) to call the OJS/OMP/OPS logout afterwards.
	 *
	 * @param $args
	 * @param $request
	 */
	function signOut($args, $request)
	{
		if (Validation::isLoggedIn()) {
			$plugin = PluginRegistry::getPlugin('generic', KEYCLOAK_PLUGIN_NAME);
			$router = $request->getRouter();
			$context = Application::get()->getRequest()->getContext();
			$contextId = ($context == null) ? 0 : $context->getId();
			$settings = $plugin->getSetting($contextId, 'openIDSettings');
			if (isset($settings)) {
				$settings = json_decode($settings, true);
				if (key_exists('logoutUrl', $settings) && !empty($settings['logoutUrl']) && key_exists('clientId', $settings)) {
					$request->redirectUrl(
						$settings['logoutUrl'].
						'?client_id='.$settings['clientId'].
						'&redirect_uri='.$router->url($request, $context, "login", "signOutOjs")
					);
				}
				$request->redirect(Application::get()->getRequest()->getContext(), 'login', 'signOutOjs');
			}
		}
		$request->redirect(Application::get()->getRequest()->getContext(), 'index');
	}


	/**
	 * Sign a user out.
	 * This is called after oauth logout via redirect_uri parameter.
	 *
	 * @param $args
	 * @param $request
	 */
	function signOutOjs($args, $request)
	{
		if (Validation::isLoggedIn()) {
			Validation::logout();
		}
		$request->redirect(Application::get()->getRequest()->getContext(), 'index');
	}


}

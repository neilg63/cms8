<?php

namespace Drupal\ecwid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google_Analytics settings for this site.
 */

class EcwidSettingsForm extends ConfigFormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'jsonstyles_admin_settings';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return ['jsonstyles.settings'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$config = $this->config('ecwid.settings');

		$form['general'] = [
			'#type'  => 'details',
			'#title' => $this->t('General settings'),
			'#open'  => TRUE,
		];

		$form['general']['store_id'] = [
			'#default_value' => $config->get('store_id'),
			'#description'   => $this->t('required to synchronise products between Ecwid and Drupal'),
			'#maxlength'     => 64,
			'#placeholder'   => 'Store ID: 12345678',
			'#required'      => TRUE,
			'#size'          => 32,
			'#title'         => $this->t('Store ID'),
			'#type'          => 'textfield',
		];

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$config = \Drupal::service('config.factory')->getEditable('ecwid.settings');
		$config
			->set('store_id', $form_state->getValue('store_id'))
			->save();
		parent::submitForm($form, $form_state);
	}

}

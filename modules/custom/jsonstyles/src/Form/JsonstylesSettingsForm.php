<?php

namespace Drupal\jsonstyles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google_Analytics settings for this site.
 */

class JsonstylesSettingsForm extends ConfigFormBase {

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
		$config = $this->config('jsonstyles.settings');

		$form['general'] = [
			'#type'  => 'details',
			'#title' => $this->t('General settings'),
			'#open'  => TRUE,
		];

		$form['general']['copyright'] = [
			'#default_value' => $config->get('copyright'),
			'#description'   => $this->t('Use !year for a dynamic year'),
			'#maxlength'     => 64,
			'#placeholder'   => '© !year Blustin Design',
			'#required'      => TRUE,
			'#size'          => 32,
			'#title'         => $this->t('Copyright notice'),
			'#type'          => 'textfield',
		];

		$form['general']['email'] = [
			'#default_value' => $config->get('email'),
			'#description'   => $this->t('Contact email address, displayed in the footer. Leave blank to remove from the footer'),
			'#maxlength'     => 256,
			'#placeholder'   => '',
			'#required'      => FALSE,
			'#size'          => 48,
			'#title'         => $this->t('Contact email'),
			'#type'          => 'textfield',
		];

		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array&$form, FormStateInterface $form_state) {
		$config = $this->config('jsonstyles.settings');
		$config
			->set('copyright', $form_state->getValue('copyright'))
			->set('email', $form_state->getValue('email'))
			->save();

		parent::submitForm($form, $form_state);
	}

}

<?php
namespace Frozennode\Administrator\Fields;

use Frozennode\Administrator\Includes\Multup;
use Frozennode\Administrator\Fields\Relationships\HasMany;

class Images extends HasMany
{
    /**
     * The specific defaults for the image class
     *
     * @var array
     */
    protected $imageDefaults = array(
        'sizes' => array(),
    );

    /**
     * The specific rules for the image class
     *
     * @var array
     */
    protected $imageRules = array(
        'sizes' => 'array',
    );

    /**
     * The specific defaults for subclasses to override
     *
     * @var array
     */
    protected $defaults = array(
        'relationship' => true,
        'external' => true,
        'name_field' => 'name',
        'options_sort_field' => false,
        'options_sort_direction' => 'ASC',
        'table' => '',
        'column' => '',
        'foreign_key' => false,
        'multiple_values' => false,
        'options' => array(),
        'self_relationship' => false,
        'autocomplete' => false,
        'num_options' => 10,
        'search_fields' => array(),
        'constraints' => array(),
        'load_relationships' => false,

        //Image
        'naming' => 'random',
        'length' => 32,
        'mimes' => false,
        'size_limit' => 2,
        'display_raw_value' => false,
    );

    /**
     * The relationship-type-specific defaults for the relationship subclasses to override
     *
     * @var array
     */
    protected $relationshipDefaults = array(
        'column2' => '',
        'multiple_values' => true,
        'sort_field' => false,
    );

    /**
     * The specific rules for subclasses to override
     *
     * @var array
     */
    protected $rules = array(
        'name_field' => 'string',
        'sort_field' => 'string',
        'options_sort_field' => 'string',
        'options_sort_direction' => 'string',
        'num_options' => 'integer|min:0',
        'search_fields' => 'array',
        'options_filter' => 'callable',
        'constraints' => 'array',

        //Image
        'location' => 'required|string|directory',
        'naming' => 'in:keep,random',
        'length' => 'integer|min:0',
        'mimes' => 'string',
    );

    /**
     * This static function is used to perform the actual upload and resizing using the Multup class
     *
     * @return array
     */
    public function doUpload()
    {
        //use the multup library to perform the upload
        $result = Multup::open('file', 'image|max:' . $this->getOption('size_limit') * 1000, $this->getOption('location'),
            $this->getOption('naming') === 'random')
            ->sizes($this->getOption('sizes'))
            ->set_length($this->getOption('length'))
            ->upload();

        return $result[0];
    }

    /**
     * Gets all rules
     *
     * @return array
     */
    public function getRules()
    {
        $rules = parent::getRules();

        return array_merge($rules, $this->imageRules);
    }

    /**
     * Gets all default values
     *
     * @return array
     */
    public function getDefaults()
    {
        $defaults = parent::getDefaults();

        return array_merge($defaults, $this->imageDefaults);
    }

    /**
     * Builds a few basic options
     */
    public function build()
    {
        parent::build();

        //set the upload url depending on the type of config this is
        $url = $this->validator->getUrlInstance();
        $route = $this->config->getType() === 'settings' ? 'admin_settings_file_upload' : 'admin_file_upload';

        //var_dump($this->suppliedOptions);
        //set the upload url to the proper route
        $this->suppliedOptions['upload_url'] = $url->route($route, array($this->config->getOption('name'), $this->suppliedOptions['field_name']));
    }

    /**
     * Fill a model with input data
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param mixed $input
     *
     * @return array
     */
    public function fillModel(&$model, $input)
    {
        // $input is an array of all foreign key IDs
        //
        // $model is the model for which the above answers should be associated to
        $fieldName = $this->getOption('field_name');
        $input = $input ? explode(',', $input) : array();
        $relationship = $model->{$fieldName}();

        //var_dump(get_class($model), (string)$fieldName);

        // get the plain foreign key so we can set it to null:
        $fkey = $relationship->getPlainForeignKey();

        $relatedObjectClass = get_class($relationship->getRelated());

        // first we "forget all the related models" (by setting their foreign key to null)
        foreach ($relationship->get() as $related) {
            //$related->$fkey = null; // disassociate
            //$related->save();
        }

        // now associate new ones: (setting the correct order as well)
        $i = 0;

        $deleteImages = call_user_func($relatedObjectClass . '::where', 'product_id', $model->id);
        $deleteImages->delete();

        foreach ($input as $foreign_id) {
            $relatedObject = new $relatedObjectClass();

            $relatedObject->path = $foreign_id;
            $relatedObject->product_id = $model->id;

            $isExisting = call_user_func($relatedObjectClass . '::where', 'path', $foreign_id);

            if ($isExisting->first()) {
                continue;
            }

            if ($sortField = $this->getOption('sort_field')) {
                $relatedObject->$sortField = $i++;
            }

            $relationship->save($relatedObject);
        }
    }

}
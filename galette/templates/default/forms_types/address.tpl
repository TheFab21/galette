<p>
{include
    file="forms_types/text.tpl"
    name=$entry->field_id
    id=$entry->field_id
    value=$member->address|escape
    required=$entry->required
    disabled=$entry->disabled
    label=$entry->label
    notag=true
    elt_class="large"
}
</p>

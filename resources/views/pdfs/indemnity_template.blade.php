<p style="padding-left: 880px;">{{logo}}</p>
<p>{{site_name}}</p>
<p>
    {{site_reg_no}}
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    INDEMNITY FORM
</p>

<table style="border:1px solid white; border-collapse: collapse; width: 100%;">
    <tbody>
        <tr>
            <td>
                <table style="border:1px solid black; border-collapse: collapse; width: 79%; height: 85px;">
                    <tbody>
                        <tr>
                            <td>Company:</td>
                            <td>Participant:</td>
                        </tr>
                        <tr>
                            <td>{{site_name}}</td>
                            <td>{{customer_name}}</td>
                        </tr>
                        <tr>
                            <td>{{site_address}}</td>
                            <td>{{customer_contact_number}}</td>
                        </tr>
                        <tr>
                            <td>{{site_address_code}}</td>
                            <td>{{customer_email}}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td>
                <table style="border:1px solid black; border-collapse: collapse; width: 100%; height: 73px;">
                    <tbody>
                        <tr>
                            <td>Booking Ref:</td>
                            <td>{{booking_ref}}</td>
                        </tr>
                        <tr>
                            <td>Date:</td>
                            <td>{{date}}</td>
                        </tr>
                        <tr>
                            <td>Guide:</td>
                            <td>{{guide_name}}</td>
                        </tr>
                        <tr>
                            <td>Safety Guide:</td>
                            <td>{{safety_guide_name}}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>

<p>&nbsp;</p>

<!-- PARTICIPANT DETAILS -->
<table style="border:1px solid black; border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th style="border:1px solid black;">Full Name</th>
            <th style="border:1px solid black;">Age</th>
            <th style="border:1px solid black;">Gender</th>
            <th style="border:1px solid black;">Country</th>
            <th style="border:1px solid black;">Province</th>
            <th style="border:1px solid black;">Medical?</th>
            <th style="border:1px solid black;">Medical Detail</th>
            <th style="border:1px solid black;">Fit?</th>
            <th style="border:1px solid black;">Minor?</th>
        </tr>
    </thead>
    <tbody>
        {{#each participants}}
        <tr>
            <td style="border:1px solid black;">{{name}}</td>
            <td style="border:1px solid black;">{{age}}</td>
            <td style="border:1px solid black;">{{gender}}</td>
            <td style="border:1px solid black;">{{country}}</td>
            <td style="border:1px solid black;">{{province}}</td>
            <td style="border:1px solid black;">{{is_medical}}</td>
            <td style="border:1px solid black;">{{medical_detail}}</td>
            <td style="border:1px solid black;">{{is_fit}}</td>
            <td style="border:1px solid black;">{{is_minor}}</td>
        </tr>
        {{/each}}
    </tbody>
</table>

<p>&nbsp;</p>

<!-- CHILDREN DETAILS (if any) -->
{{#if children.length}}
<p><strong>Children Included Under This Indemnity:</strong></p>
<table style="border:1px solid black; border-collapse: collapse; width: 100%;">
    <thead>
        <tr>
            <th style="border:1px solid black;">Child Name</th>
            <th style="border:1px solid black;">Age</th>
            <th style="border:1px solid black;">Gender</th>
        </tr>
    </thead>
    <tbody>
        {{#each children}}
        <tr>
            <td style="border:1px solid black;">{{name}}</td>
            <td style="border:1px solid black;">{{age}}</td>
            <td style="border:1px solid black;">{{gender}}</td>
        </tr>
        {{/each}}
    </tbody>
</table>
{{/if}}

<p>&nbsp;</p>

<!-- SIGNATURE -->
<table style="border-collapse: collapse; width: 100%;" border="1">
    <tbody>
        <tr>
            <td style="width: 50%; padding: 10px;">
                <strong>Signature of Participant / Guardian:</strong><br><br>
                {{#if signature}}
                    <img src="{{signature}}" alt="Signature" style="max-height:80px;">
                {{else}}
                    ___________________________
                {{/if}}
            </td>
            <td style="width: 50%; padding: 10px;">
                <strong>Date:</strong> {{date}}
            </td>
        </tr>
    </tbody>
</table>

<p>&nbsp;</p>

<!-- DECLARATION -->
<p>
    I, {{customer_name}}, hereby declare that I understand and accept the risks involved in this activity.
    I confirm that I am physically fit to participate and agree to abide by all safety instructions.
</p>

<p style="font-size: 12px; margin-top: 20px;">
    © {{year}} {{site_name}} — All rights reserved.
</p>

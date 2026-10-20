<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class PublicSite extends BaseConfig
{
    // Category guides from the approved brief, not inventory or database records.
    public array $products = [
        ['name' => 'Solar panels', 'icon' => 'panel', 'tag' => 'Capture the sunlight', 'description' => 'The starting point for turning sunlight into electricity.', 'details' => 'Panels generate direct-current electricity from sunlight. Suitable panel types, quantity and placement depend on the roof, available space, shading and energy needs.'],
        ['name' => 'Inverters', 'icon' => 'inverter', 'tag' => 'Make energy usable', 'description' => 'Convert solar electricity for use around your home or business.', 'details' => 'An inverter converts direct current into alternating current. Grid-connected, hybrid and off-grid systems have different requirements; compatibility should be checked during system design.'],
        ['name' => 'Batteries', 'icon' => 'battery', 'tag' => 'Store energy for later', 'description' => 'Explore energy storage as part of a compatible solar system.', 'details' => 'Batteries can store electricity for later use. Backup capability depends on the complete system design, supported circuits, inverter and battery capacity; a battery alone does not guarantee backup power.'],
        ['name' => 'Mounting equipment', 'icon' => 'mount', 'tag' => 'Build on a solid foundation', 'description' => 'Structures and fittings that support a suitable panel layout.', 'details' => 'Mounting systems secure panels to a suitable surface. Roof condition, structure, wind exposure and the installation environment must be considered when selecting equipment.'],
        ['name' => 'Wires & connections', 'icon' => 'wire', 'tag' => 'Bring the system together', 'description' => 'Electrical connections selected for the needs of the system.', 'details' => 'Solar installations require appropriately rated cables, connectors and protection. Cable sizing and routing should be designed and installed by qualified personnel.'],
    ];

    public array $services = [
        ['name' => 'Consultation', 'icon' => 'sun', 'description' => 'Start with your energy goals, available space and questions about going solar.', 'details' => 'A consultation helps identify your electricity use and site considerations before deciding what kind of system may be suitable.'],
        ['name' => 'Solar system design', 'icon' => 'panel', 'description' => 'Consider how panels, an inverter and other components can work together.', 'details' => 'Design considers energy demand, available space, shading and equipment compatibility. A site assessment is needed before final specifications can be confirmed.'],
        ['name' => 'Permitting', 'icon' => 'document', 'description' => 'Understand the approvals that may apply to a proposed installation.', 'details' => 'Requirements depend on the installation, location and electricity provider. Applicable approvals and documentation should be confirmed before work proceeds.'],
        ['name' => 'Installation', 'icon' => 'mount', 'description' => 'Bring a planned system into place with attention to the site and design.', 'details' => 'Installation covers positioning and connecting compatible equipment according to an agreed design and applicable safety requirements.'],
        ['name' => 'Maintenance', 'icon' => 'tool', 'description' => 'Keep system condition and ongoing care part of the conversation.', 'details' => 'Maintenance may include inspection and suitable cleaning or servicing. The appropriate scope depends on the equipment, environment and manufacturer guidance.'],
        ['name' => 'Repair', 'icon' => 'inverter', 'description' => 'Assess a reported issue and determine the appropriate next steps.', 'details' => 'Troubleshooting starts with understanding the symptoms and inspecting the system. Repair scope, parts and timing require assessment; do not attempt electrical repairs yourself.'],
        ['name' => 'Monitoring', 'icon' => 'monitor', 'description' => 'Understand system performance through compatible monitoring options.', 'details' => 'Monitoring can help show energy production and flag changes in performance. Features depend on the installed equipment and connectivity; continuous remote monitoring is not implied.'],
    ];

    public array $faqs = [
        ['question' => 'What is solar energy?', 'answer' => 'Solar energy comes from sunlight. Solar photovoltaic panels convert that light into electricity, which a compatible system can make usable for a home or business.'],
        ['question' => 'What products does Sun Son Solar offer?', 'answer' => 'Our product categories include solar panels, inverters, batteries, mounting equipment and wires. This page is a category guide; specific models, stock and pricing are not yet published.'],
        ['question' => 'What services can I explore?', 'answer' => 'Explore consultation, solar system design, permitting, installation, maintenance, repair and monitoring. The service guides above explain the general scope; specific work needs to be discussed and confirmed.'],
        ['question' => 'Will solar reduce my electricity bill?', 'answer' => 'Solar can reduce the electricity you purchase, depending on system size, sunlight, shading, your usage patterns, tariffs and other conditions. Savings vary and are not guaranteed.'],
        ['question' => 'How can I request a consultation or book a service?', 'answer' => 'Online consultation requests and service booking are not available yet. You can explore the service guides and create an account now. Booking information will be updated when that feature is ready.'],
        ['question' => 'Can I manage a booking through my account?', 'answer' => 'You can currently register, sign in and sign out. Booking submission, scheduling, rescheduling and tracking are not available yet. Creating an account does not submit a service request.'],
        ['question' => 'Where is the company based, and how can I contact it?', 'answer' => 'Sun Son Solar is based in Pasig City, Philippines. An official phone number, email address and exact office address have not yet been provided for this website. Verified contact details will be added here once confirmed.'],
    ];
}

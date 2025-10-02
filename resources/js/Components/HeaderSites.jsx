import { router } from "@inertiajs/react";



export default function HeaderSites({ permissions, isOpenTab, setIsOpenTab }) {
  console.log('permissions ', permissions);
  function toggleDropdown(tab, routeName) {
    setIsOpenTab(prev => (prev === tab ? '' : tab));
    router.get(route(routeName), {}, {
      preserveScroll: true,
      preserveState: true,
    });
  }

  return (
    <div className="flex pt-2 space-x-6 border-b border-gray-200 mb-2">
      {permissions?.teams_access && (
        <button id="Teams-tab" className={` ${isOpenTab === 'Teams' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer`} onClick={() => toggleDropdown('Teams', 'admin.teams.index')} >Site</button>
      )}
      {permissions?.users_access && (
        <button id="Users-tab" className={` ${isOpenTab === 'Users' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={() => toggleDropdown('Users', 'admin.users.index')} >Users</button>
      )}
      {permissions?.user_alerts_access && (
        <button id="User_Alerts-tab" className={` ${isOpenTab === 'User_Alerts' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('User_Alerts', 'admin.user-alerts.index')} >User Alerts</button>
      )}
      {permissions?.invoice_templates_access && (
        <button id="Invoice-Template-tab" className={` ${isOpenTab === 'Invoice_Templates' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Invoice_Template', 'admin.invoice-templates.index')} >Invoice-Templates</button>
      )}
      {permissions?.questionnaires_access && (
        <button id="Questionnaires-tab" className={` ${isOpenTab === 'Questionnaires' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Questionnaires', 'admin.questionnaire.index')} >Questionnaires</button>
      )}
      {permissions?.manifests_access && (
        <button id="Manifests-tab" className={` ${isOpenTab === 'Manifests' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Manifests', 'admin.manifests.index')} >Manifests</button>
      )}
    </div>
  );
}
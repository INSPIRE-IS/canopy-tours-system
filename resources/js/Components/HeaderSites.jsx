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
      {permissions?.boarding_pass_templates_access && (
        <button id="Boarding-Pass-tab" className={` ${isOpenTab === 'Boarding_Pass' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Boarding_Pass', 'admin.boardingpass.index')} >Boarding Pass</button>
      )}
      {permissions?.permissions_access && false && (
        <button id="permissions-tab" className={` ${isOpenTab === 'permissions' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={() => toggleDropdown('permissions', 'admin.permissions.index')} >permissions</button>
      )}
      {permissions?.roles_access && false && (
        <button id="roles-tab" className={` ${isOpenTab === 'roles' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={() => toggleDropdown('roles', 'admin.roles.index')} >roles</button>
      )}

      {permissions?.user_alerts_access && false && (
        <button id="User-Alerts-tab" className={` ${isOpenTab === 'User_Alerts' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('User_Alerts', 'admin.user-alerts.index')} >User Alerts</button>
      )}

      {permissions?.invoice_templates_access && false && (
        <button id="Invoice-Template-tab" className={` ${isOpenTab === 'Invoice_Templates' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Invoice_Template', 'admin.invoice-templates.index')} >Invoice-Templates</button>
      )}
      {permissions?.booking_vouchers_access && false && (
        <button id="vouchers-tab" className={` ${isOpenTab === 'vouchers' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('vouchers', 'admin.booking_vouchers.index')} >Booking Details</button>
      )}
      {permissions?.questionnaires_access && false && (
        <button id="Questionnaires-tab" className={` ${isOpenTab === 'Questionnaires' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Questionnaires', 'admin.questionnaire.index')} >Questionnaires</button>
      )}
      {permissions?.manifests_access && false && (
        <button id="Manifests-tab" className={` ${isOpenTab === 'Manifests' ? 'border-b-[5px] border-primary-7' : ''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={() => toggleDropdown('Manifests', 'admin.manifests.index')} >Manifests</button>
      )}
    </div>
  );
}
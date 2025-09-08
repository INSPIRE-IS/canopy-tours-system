import { router } from "@inertiajs/react";



export default function HeaderSites({ isOpenTab, setIsOpenTab }) {

    function toggleDropdown (tab, routeName) {
        setIsOpenTab(prev => (prev === tab ? '' : tab));
        router.get(route(routeName), {}, {
        preserveScroll: true,
        preserveState: true,
        });
    }

    return (
          <div className="flex pt-2 space-x-6 border-b border-gray-200 mb-2">
            <button id="Users-tab" className={` ${isOpenTab === 'Users' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-active pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Users', 'admin.users.index')} >Users</button>
            <button id="Teams-tab" className={` ${isOpenTab === 'Teams' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer`} onClick={()=> toggleDropdown('Teams', 'admin.teams.index')} >Teams</button>
            <button id="User_Alerts-tab" className={` ${isOpenTab === 'User_Alerts' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('User_Alerts', 'admin.user-alerts.index')} >User Alerts</button>
            <button id="User_Alerts-tab" className={` ${isOpenTab === 'User_Alerts' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('User_Alerts', 'admin.user-alerts.index')} >Questionairre</button>
            <button id="User_Alerts-tab" className={` ${isOpenTab === 'User_Alerts' ? 'border-b-[5px] border-primary-7' :''} tab-button px-4 py-[11px] text-tab-inactive pb-2 cursor-pointer flex items-center gap-1`} onClick={()=> toggleDropdown('User_Alerts', 'admin.user-alerts.index')} >Manifest</button>
          </div>
    );
}
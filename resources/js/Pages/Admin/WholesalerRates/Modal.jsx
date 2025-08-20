import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const Modal = ({ isOpen, onClose, mode, item = null, permissions, wholesalers, seasons,auth }) => {

    const { data, setData, post, put, errors, reset, processing } = useForm({
        wholesaler_id: "",
        season_id: "",
        price_adult: "",
        price_child: "",
        price_guide: "",
        team_id: auth?.user?.team_id,
        created_by_id: auth?.user?.id,
        updated_by_id: auth?.user?.id,
    });

    useEffect(() => {
        if (item && mode !== "create") {
            setData({
                wholesaler_id: item?.wholesaler_id || "",
                season_id: item?.season_id || "",
                price_adult: item?.price_adult || "",
                price_child: item?.price_child || "",
                price_guide: item?.price_guide || "",
                team_id: auth?.user?.team_id,
                created_by_id: item.created_by_id || auth?.user?.id,
                updated_by_id: item.updated_by_id || auth?.user?.id,
            });
        } else if (mode === "create") {
            reset(); // Clear form for create mode
        }
    }, [mode,item,auth]);


  //const handleChange = (target, value) => setData(target, value);
      const handleChange = (target, value) => {
        setData(target, value);
        //setData("voucher_code", e.target.value);
    };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (mode === "create") {
      console.log('data ', data);
      post(route('admin.wholesaler_rates.store'), { onSuccess: () => onClose() });
    } else if (mode === "edit") {
      put(route('admin.wholesaler_rates.update', item.id), { onSuccess: () => onClose() });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
      <div className="bg-white rounded-lg shadow-lg p-6 w-[50%] h-[98%] overflow-y-auto">
        <h2 className="text-xl font-bold mb-4">
          {mode === "create" && "Wholesaler Rate Create"}
          {mode === "edit" && "Wholesaler Rate Edit"}
          {mode === "view" && "Wholesaler Rate View"}
        </h2>

        <form onSubmit={handleSubmit}>
          <div className="flex flex-col gap-4">
            <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
                        <CustomSelect
                          label="Wholesaler"
                          name="wholesaler_id"
                          value={data.wholesaler_id}
                          //optionsUrl={route('wholesalers.options')}
                          options={wholesalers}
                          mode={mode}
                          onChange={(e) => handleChange(e.target.name, e.target.value)}
                          errors={errors.wholesaler_id}
                        />
                        <CustomSelect
                          label="Season"
                          name="season_id"
                          value={data.season_id}
                          //optionsUrl={route('seasons.options')}
                          options={seasons}
                          mode={mode}
                          onChange={(e) => handleChange(e.target.name, e.target.value)}
                          errors={errors.season_id}
                        />  
            </div>

              <div className="flex flex-row w-full items-center gap-2 justify-between mb-4">
              <InputText title="Adult Price" field="price_adult" value={data.price_adult} mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Child Price" field="price_child" value={data.price_child} mode={mode} handleChange={handleChange} errors={errors} />
              <InputText title="Guide Price" field="price_guide" value={data.price_guide} mode={mode} handleChange={handleChange} errors={errors} />              
              </div>

          </div>

          <div className="flex justify-center gap-4 mt-6">
            <OutlineButton title="Cancel" onClick={onClose} width="w-[180px]" />
            {mode !== "view" && (
              <BlueButton title={mode === "create" ? "Create" : "Update"} width="w-[180px]" onClick={handleSubmit} disabled={processing} type="submit" />
            )}
          </div>
        </form>
      </div>
    </div>
  );
};

export default Modal;

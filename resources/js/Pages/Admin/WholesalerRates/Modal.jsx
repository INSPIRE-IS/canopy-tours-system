import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import InputText from "@/Components/InputText";
import CustomSelect from "@/Components/CustomSelect";
import OutlineButton from "@/Components/OutlineButton";
import BlueButton from "@/Components/BlueButton";

const Modal = ({ isOpen, onClose, mode, item = null, permissions, wholesalers, services,auth }) => {
    const [seasons, setSeasons] = useState([]);
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

    useEffect(() => {
        const selectedService = services.find(s => {
            console.log('service', s);
            console.log('service_id ', data.service_id);
            const selected = s.id === parseInt(data.service_id);
            console.log(selected);

            return selected;
        });
        console.log(selectedService);
        const seasons = selectedService ? setSeasons(selectedService.seasons) : [];
        console.log('seasons', selectedService?.seasons);

    }, [data.service_id]);

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
                            label="Service"
                            name="service_id"
                            value={data.service_id}
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.service_id}
                            options={services}
                        /> 
                        <CustomSelect
                            label="Season"
                            name="season_id"
                            value={data.season_id}
                            
                            onChange={(e) => handleChange(e.target.name, e.target.value)}
                            error={errors.season_id}
                            options={seasons}
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

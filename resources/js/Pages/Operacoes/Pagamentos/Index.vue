<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Pagamentos</h1>
          </div>
          <div class="col-sm-6"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                <Link
                  :href="route('mc.pagamentos.create')"
                  class="btn btn-info float-right"
                  type="button"
                >
                  Novos Pagamentos
                </Link>
                
                <button
                  class="btn btn-success float-right mr-1"
                  type="button"
                  @click="imprimirEXCEL"
                >
                  <i class="fas fa-file-excel"></i>
                  EXCEL
                </button>
                
                <button
                  class="btn btn-danger float-right mr-1"
                  type="button"
                  @click="imprimirPDF"
                >
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº</th>
                      <th>Matricula</th>
                      <th>Serviço Pago</th>
                      <th>Nome</th>
                      <th>Curso</th>
                      <th>Data</th>
                      <th class="text-right">Total Pago</th>
                    </tr>
                  </thead>
                  <tbody>
                  
                    <tr v-for="item in items.data" :key="item.Codigo">
                      <td>{{ item.Codigo }}</td>
                      <td>{{ item.matricula }}</td>
                      <td>{{ item.servico }}</td>
                      <td>{{ item.Nome_Completo }}</td>
                      <td>{{ item.curso }}</td>
                      <td>{{ item.DataRegisto }}</td>
                      <td class="text-right">{{ formatValor(item.Totalgeral) }} Kz</td>
                    </tr>

                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                TOTAL REGISTROS: {{ items.total }}
                </Link>
                <Paginacao :links="items.links"
                    :prev="items.prev_page_url"
                    :next="items.next_page_url" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
  
<script>
import { sweetSuccess, sweetError } from "../../../components/Alert";
import Paginacao from "../../../Shared/Paginacao";
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["items"],
  components: { Link, Paginacao },
  data() {
    return {};
  },

  mounted() {},
  methods: {
    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
    
    imprimirPDF() {
      window.open(`/depositos/pdf`, "_blank");
    },
    
    imprimirEXCEL() {
      window.open(`/depositos/excel`, "_blank");
    },
    
    
  },
};
</script>
  
  
  
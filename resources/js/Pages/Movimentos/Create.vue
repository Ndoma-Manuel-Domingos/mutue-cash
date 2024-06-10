<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-info">CRIAR MOVIMENTOS</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/movimentos">Listagem</a></li>
              <li class="breadcrumb-item active">Criar Movimentos</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">

            <div class="col-12 col-md-12">
                <form @submit.prevent="submit">
                    <div class="row">
                      <div class="col-12 col-md-6">
                        <div class="card">
                            <div class="card-header"></div>
            
                            <div class="card-body">
                              <div class="row">
                              
                                <div class="col-12 col-md-2 mb-4">
                                  <label for="exercicio_id" class="form-label">Exercício</label>
                                    <Select2 v-model="form.exercicio_id" id="exercicio_id" disabled
                                      :options="exercicios" :settings="{ width: '100%' }" 
                                    />
                                  <span class="text-danger" v-if="form.errors && form.errors.exercicio_id">{{ form.errors.exercicio_id }}</span>
                                </div>
                                
                                <div class="col-12 col-md-2 mb-4">
                                  <label for="periodo_id" class="form-label">Período</label>
                                    <Select2 v-model="form.periodo_id" id="periodo_id"
                                      :options="periodos" :settings="{ width: '100%' }" 
                                    />
                                  <span class="text-danger" v-if="form.errors && form.errors.periodo_id">{{ form.errors.periodo_id }}</span>
                                </div>
                                
                                <div class="col-12 col-md-2 mb-4">
                                  <label for="dia_id" class="form-label">Dia</label>
                                    <Select2 v-model="form.dia_id" id="dia_id"
                                      :options="dias" :settings="{ width: '100%' }" 
                                    />
                                  <span class="text-danger" v-if="form.errors && form.errors.dia_id">{{ form.errors.dia_id }}</span>
                                </div>
                                
                                <!-- <div class="col-12 col-md-2 mb-4">
                                  <label for="data_lancamento" class="form-label">Data</label>
                                  <input type="date" id="data_lancamento" v-model="form.data_lancamento" class="form-control" >
                                  <span class="text-danger" v-if="form.errors && form.errors.data_lancamento">{{ form.errors.data_lancamento }}</span>
                                </div> -->
                                
                                <div class="col-12 col-md-6 mb-4">
                                  <label for="lancamento_atual" class="form-label">Lançamento Actual</label>
                                  <input type="number" id="lancamento_atual" v-model="form.lancamento_atual" class="form-control" >
                                  <span class="text-danger" v-if="form.errors && form.errors.lancamento_atual">{{ form.errors.lancamento_atual }}</span>
                                </div>
                                
                                <div class="col-12 col-md-6 mb-4">
                                  <label for="diario_id" class="form-label">Diários</label>
                                  <Select2 v-model="form.diario_id" id="diario_id"
                                    :options="diarios" :settings="{ width: '100%' }" 
                                    @select="getDiario($event)"
                                  />
                                  <span class="text-danger" v-if="form.errors && form.errors.diario_id">{{ form.errors.diario_id }}</span>
                                </div>
                                
                                <div class="col-12 col-md-6 mb-4">
                                  <label for="tipo_documento_id" class="form-label">Tipo De Documento</label>
                                    <Select2 v-model="form.tipo_documento_id" id="tipo_documento_id"
                                      :options="tipo_documentos" :settings="{ width: '100%' }" 
                                    />
                                  <span class="text-danger" v-if="form.errors && form.errors.tipo_documento_id">{{ form.errors.tipo_documento_id }}</span>
                                </div>
                                
                                <div class="col-12 col-md-12 mb-4">
                                  <label for="sub_conta_id" class="form-label">Contas</label>
                                    <Select2 v-model="form.sub_conta_id" id="sub_conta_id" 
                                      :options="contas" :settings="{ width: '100%' }" 
                                      @select="addSubContaMovimento($event)"
                                    />
                                  <span class="text-danger" v-if="form.errors && form.errors.sub_conta_id">{{ form.errors.sub_conta_id }}</span>
                                </div>
                                
                                <div class="col-12 col-md-12 mb-4">
                                  <label for="sub_conta_id" class="form-label">Descrição</label>
                                  <textarea v-model="form.descricao" class="form-control" id="" cols="30" rows="2"></textarea>
                                </div>
                              
                              </div>
                            </div>
            
                            <div class="card-footer">
                              <button class="btn btn-success">
                                <i class="fas fa-save"></i> Salvar
                              </button>
                            </div>
                        </div>
                      </div>
                      <div class="col-12 col-md-6">
                        <div class="card" style="height: 590px;">
                          <div class="card-header"></div>
                          <div class="card-body" style="overflow-y: scroll; height: 470px;">
                            <table class="table table-sm" style="width: 100%;">
                              <thead>
                                <tr>
                                  <th width="400px">Conta</th>
                                  <th>Debito</th>
                                  <th>Crédito</th>
                                  <th>IVA</th>
                                  <th>Descrição</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody v-for="item in item_movimentos" :key="item">
                                <tr>
                                  <td class="pt-3">{{ item.subconta.numero }} - {{ item.subconta.designacao }}</td>
                                  <td><input type="text" class="form-control border-0 py-0" v-model="item.debito" @keypress="validateInput" @input="formatInputDebito(item)" @keydown.enter="input_valor_debito(item)"></td>
                                  <td><input type="text" class="form-control border-0" v-model="item.credito" @keypress="validateInput" @input="formatInputCredito(item)" @keydown.enter="input_valor_credito(item)"></td>
                                  <td><input type="text" class="form-control border-0" v-model="item.iva" @keypress="validateInput" @keydown.enter="input_valor_iva(item)"></td>
                                  <td><input type="text" class="form-control border-0" v-model="item.descricao" @keydown.enter="input_valor_descricao(item)" placeholder="Descrição"></td>
                                  <td class="d-flex">
                                    <a @click="remover_item_movimento(item)" class="text-danger pt-3"><i class="fas fa-times"></i></a>
                                  </td>
                                </tr>
                              </tbody>
                              
                            </table>
                          </div>
                          <div class="card-footer">
                            <table class="table">
                                  <tr>
                                    <th width="400px">Total</th>
                                    <th>{{ formatValor(resultados.total_debito ?? 0) }}</th>
                                    <th>{{ formatValor(resultados.total_credito ?? 0) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                  </tr>
                            </table>
                          </div>
                          
                        </div>
                      </div>
                    </div>
                </form>
            </div>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
    
<script>
export default {
  props: [
    "diarios",
    "exercicios",
    "tipo_documentos",
    "contas",
    "item_movimentos",
    "ultimo_movimento",
    "resultados",
    "periodos",
  ],
  computed: {
    user() {
      return this.$page.props.auth.user;
    },
    sessions() {
      return this.$page.props.sessions.empresa_sessao;
    },
    sessions_exercicio() {
      return this.$page.props.sessions.exercicio_sessao;
    },
    periodo_sessao() {
      return this.$page.props.sessions.periodo_sessao;
    },
  },
  data() {
    return {
      
      estados: [
        {'id': "activo", 'text': "Activo"},
        {'id': "desactivo", 'text': "Desactivo"},
      ],
      
      dias: [
        {'id': "1", 'text': "1"},
        {'id': "2", 'text': "2"},
        {'id': "3", 'text': "3"},
        {'id': "4", 'text': "4"},
        {'id': "5", 'text': "5"},
        {'id': "6", 'text': "6"},
        {'id': "7", 'text': "7"},
        {'id': "8", 'text': "8"},
        {'id': "9", 'text': "9"},
        {'id': "10", 'text': "10"},
        {'id': "11", 'text': "11"},
        {'id': "12", 'text': "12"},
        {'id': "13", 'text': "13"},
        {'id': "14", 'text': "14"},
        {'id': "15", 'text': "15"},
        {'id': "16", 'text': "16"},
        {'id': "17", 'text': "17"},
        {'id': "18", 'text': "18"},
        {'id': "19", 'text': "19"},
        {'id': "20", 'text': "20"},
        {'id': "21", 'text': "21"},
        {'id': "22", 'text': "22"},
        {'id': "23", 'text': "23"},
        {'id': "24", 'text': "24"},
        {'id': "25", 'text': "25"},
        {'id': "26", 'text': "26"},
        {'id': "27", 'text': "27"},
        {'id': "28", 'text': "28"},
        {'id': "29", 'text': "29"},
        {'id': "30", 'text': "30"},
        {'id': "31", 'text': "31"},
      ],
      
      debito: 0,
      credito: 0,
      
      dataAtual: new Date(),
      
      tipo_documentos: [],
      item_movimentos: [],
      resultados: [],
      
      form: this.$inertia.form({
        exercicio_id: "",
        periodo_id: "",
        dia_id: "",
        lancamento_atual: this.ultimo_movimento + 1,
        diario_id: "",
        tipo_documento_id: "",
        descricao: "",
      }),
    };
  },
  mounted() {
    this.form.exercicio_id = this.sessions_exercicio ? this.sessions_exercicio.id : "";
    this.form.periodo_id = this.periodo_sessao ? this.periodo_sessao.id : "";
    
    this.form.dia_id = this.dataAtual.getDate();
    this.form.mes_id = this.dataAtual.getMonth() + 1;
    
  },
  methods: {

    getDiario({ id, text }) {
      axios
        .get(`/get-diario/${this.form.diario_id}`)
        .then((response) => {
          
          this.form.numero = response.data.diario.numero + ".";
          this.tipo_documentos = [];
          this.tipo_documentos = response.data.tipos_documentos;
          
        })
        .catch((error) => {});
    },

    addSubContaMovimento({ id, text }) {
      axios
        .get(`/adicionar-conta-movimento/${this.form.sub_conta_id}`)
        .then((response) => {
          
          this.item_movimentos = [];
          this.item_movimentos = response.data.item_movimentos;
          this.resultados = response.data.resultados;
          
        })
        .catch((error) => {});
    },
    
    remover_item_movimento(item) {
      axios
        .get(`/remover-conta-movimento/${item.id}`)
        .then((response) => {
          
          this.item_movimentos = [];
          this.item_movimentos = response.data.item_movimentos;
          this.resultados = response.data.resultados;
          
        })
        .catch((error) => {});
     console.log(item)
    },    

    formatInputCredito(item) {
      // Implemente aqui a lógica de formatação desejada
      // Por exemplo, para formatar como moeda
      item.credito = item.credito.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    
    formatInputDebito(item) {
      // Implemente aqui a lógica de formatação desejada
      // Por exemplo, para formatar como moeda
      item.debito = item.debito.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    
    validateInput(event) {
      // Permitir apenas números
      const keyCode = event.keyCode;
      if ((keyCode < 48 || keyCode > 57) && keyCode !== 8 && keyCode !== 9 && keyCode !== 37 && keyCode !== 39) {
        event.preventDefault();
      }
    },
    
    removeFormatting(val) {
      // Remover a formatação
      return val.replace(/\D/g, '');
    },
    

    input_valor_debito(item) {
      
      if(item.subconta.tipo == "I"){
        Swal.fire({
          toast: true,
          icon: "error",
          title: "Não é permitido fazer débito nesta conta!",
          animation: false,
          position: "top-end",
          showConfirmButton: false,
          timer: 4000
        });
        
        item.debito = 0;
        return 
      }else {
      
        if(item.credito != 0){
          Swal.fire({
            toast: true,
            icon: "error",
            title: "“Não podes Debitar nesta conta!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000
          })
          item.debito = 0;
          event.preventDefault();      
          
        }else {
          
          var debito = item.debito.replace(/\D/g, '');
        
          axios
            .get(`/alterar-debito-conta-movimento/${item.id}/${debito}`)
            .then((response) => {
              
              this.item_movimentos = [];
              this.item_movimentos = response.data.item_movimentos;
              this.resultados = response.data.resultados;
              
            })
            .catch((error) => {});
                      
            event.preventDefault();
        
        }
      
        }
        
      event.preventDefault();
      
    },
    
    input_valor_credito(item) {
      
      if(item.debito != 0){
        Swal.fire({
          toast: true,
          icon: "error",
          title: "“Não podes Créditar nesta conta!",
          animation: false,
          position: "top-end",
          showConfirmButton: false,
          timer: 4000
        })
        
        item.credito = 0;
        
        event.preventDefault();      
        
      }else{
    
        var credito = item.credito.replace(/\D/g, '');
      
        axios
          .get(`/alterar-credito-conta-movimento/${item.id}/${credito}`)
          .then((response) => {
            
            this.item_movimentos = [];
            this.item_movimentos = response.data.item_movimentos;
            this.resultados = response.data.resultados;
            
          })
          .catch((error) => {});
          
          event.preventDefault();      
      }

    },
    
    input_valor_iva(item) {
      axios
        .get(`/alterar-iva-conta-movimento/${item.id}/${item.iva}`)
        .then((response) => {
          
          this.item_movimentos = [];
          this.item_movimentos = response.data.item_movimentos;
          this.resultados = response.data.resultados;
          
        })
        .catch((error) => {});
        
        event.preventDefault();
    },
    
    input_valor_descricao(item) {
      axios
        .get(`/alterar-descricao-conta-movimento/${item.id}/${item.descricao}`)
        .then((response) => {
          
          this.item_movimentos = [];
          this.item_movimentos = response.data.item_movimentos;
          this.resultados = response.data.resultados;
          
        })
        .catch((error) => {});
        
        event.preventDefault();
    },
        
    submit() {
      this.$Progress.start();
      
      // if(this.form.exercicio_id == ""){
        
      //   Swal.fire({
      //     toast: true,
      //     icon: "error",
      //     title: "Preenche o Exercícios!",
      //     animation: false,
      //     position: "top-end",
      //     showConfirmButton: false,
      //     timer: 4000
      //   })
        
      //   this.$Progress.fail();
      //   return;
      // }
      
      // if(this.form.mes_id == ""){
        
      //   Swal.fire({
      //     toast: true,
      //     icon: "error",
      //     title: "Preenche o Exercícios!",
      //     animation: false,
      //     position: "top-end",
      //     showConfirmButton: false,
      //     timer: 4000
      //   })
        
      //   this.$Progress.fail();
      //   return;
      // }
      
      if(this.resultados.total_debito != this.resultados.total_credito){
        
        Swal.fire({
          toast: true,
          icon: "error",
          title: "“Impossível fazer lançamento!",
          animation: false,
          position: "top-end",
          showConfirmButton: false,
          timer: 4000
        })
        
        this.$Progress.fail();
        return;
      }
      
      this.form.post(route("movimentos.store"), {
        preverseScroll: true,
        onSuccess: () => {
          this.form.reset();
          
          Swal.fire({
            toast: true,
            icon: "success",
            title: "Dados Salvos com Sucesso!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000
          })
          
          window.location.reload();
          
          this.$Progress.finish();
        },
        onError: (errors) => {
          
          Swal.fire({
            toast: true,
            icon: "danger",
            title: "Impossível salvar o movimento!",
            animation: false,
            position: "top-end",
            showConfirmButton: false,
            timer: 4000
          });
          
          this.$Progress.fail();
          
          console.error("Erro ao fazer requisição POST:", errors);
        },
      });

    },
    
    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
    
  },
};
</script>
    